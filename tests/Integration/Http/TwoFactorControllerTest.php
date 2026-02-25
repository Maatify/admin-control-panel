<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService;
use Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService;
use Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\DTO\TotpVerificationResultDTO;
use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Views\Twig;

final class TwoFactorControllerTest extends TestCase
{
    private TwoFactorController $controller;
    private StepUpService&MockObject $stepUpServiceMock;
    private TotpServiceInterface&MockObject $totpServiceMock;
    private DiagnosticsTelemetryService&MockObject $telemetryServiceMock;
    private Twig&MockObject $viewMock;
    private RedirectTokenServiceInterface&MockObject $redirectTokenServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stepUpServiceMock = $this->createMock(StepUpService::class);
        $this->totpServiceMock = $this->createMock(TotpServiceInterface::class);
        $this->telemetryServiceMock = $this->createMock(DiagnosticsTelemetryService::class);
        $this->viewMock = $this->createMock(Twig::class);
        $this->redirectTokenServiceMock = $this->createMock(RedirectTokenServiceInterface::class);

        $enrollmentService = new TwoFactorEnrollmentService(
            $this->stepUpServiceMock,
            $this->totpServiceMock,
            $this->telemetryServiceMock
        );

        $verificationService = new TwoFactorVerificationService(
            $this->stepUpServiceMock,
            $this->telemetryServiceMock
        );

        $this->controller = new TwoFactorController(
            $enrollmentService,
            $verificationService,
            $this->viewMock,
            $this->redirectTokenServiceMock
        );
    }

    private function createAuthenticatedRequest(string $method, string $uri): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        $adminContext = new AdminContext(1);
        $requestContext = new RequestContext('req-id', '127.0.0.1', 'test-agent');

        return $request
            ->withAttribute(AdminContext::class, $adminContext)
            ->withAttribute(RequestContext::class, $requestContext)
            ->withCookieParams(['auth_token' => 'session-token']);
    }

    public function testDoVerifyCallsStepUpServiceWithCorrectScopeAndRedirects(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
                'scope' => 'security',
                'return_to' => '/admins',
            ]);

        $response = new Response();

        $this->stepUpServiceMock
            ->expects($this->once())
            ->method('verifyTotp')
            ->with(
                1,
                'session-token',
                '123456',
                $this->isInstanceOf(RequestContext::class),
                Scope::SECURITY
            )
            ->willReturn(new TotpVerificationResultDTO(true));

        $response = $this->controller->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admins', $response->getHeaderLine('Location'));
    }

    public function testDoVerifyFails(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => 'invalid',
                'scope' => 'security',
            ]);

        $response = new Response();

        $this->stepUpServiceMock
            ->expects($this->once())
            ->method('verifyTotp')
            ->willReturn(new TotpVerificationResultDTO(false, 'Invalid code'));

        $this->viewMock
            ->expects($this->once())
            ->method('render')
            ->willReturn($response);

        $this->controller->doVerify($request, $response);
    }

    public function testDoVerifyDefaultsToLoginScopeAndDashboardRedirect(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
            ]);

        $response = new Response();

        $this->stepUpServiceMock
            ->expects($this->once())
            ->method('verifyTotp')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                Scope::LOGIN
            )
            ->willReturn(new TotpVerificationResultDTO(true));

        $response = $this->controller->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    public function testDoVerifyRejectsExternalReturnTo(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
                'scope' => 'security',
                'return_to' => 'https://evil.example/phish',
            ]);

        $response = new Response();

        $this->stepUpServiceMock
            ->expects($this->once())
            ->method('verifyTotp')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                Scope::SECURITY
            )
            ->willReturn(new TotpVerificationResultDTO(true));

        $response = $this->controller->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    public function testDoVerifyHandlesInvalidScopeGracefully(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
                'scope' => 'invalid_scope',
            ]);

        $response = new Response();

        $this->stepUpServiceMock
            ->expects($this->once())
            ->method('verifyTotp')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                Scope::LOGIN
            )
            ->willReturn(new TotpVerificationResultDTO(true));

        $this->controller->doVerify($request, $response);
    }

    public function testDoVerifyRedirectsUsingRedirectToken(): void
    {
        $token = 'valid.token';
        $path = '/protected/resource';

        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
            ])
            ->withQueryParams(['r' => $token]);

        $response = new Response();

        $this->stepUpServiceMock
            ->method('verifyTotp')
            ->willReturn(new TotpVerificationResultDTO(true));

        $this->redirectTokenServiceMock
            ->expects($this->once())
            ->method('verify')
            ->with($token)
            ->willReturn($path);

        $response = $this->controller->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($path, $response->getHeaderLine('Location'));
    }
}
