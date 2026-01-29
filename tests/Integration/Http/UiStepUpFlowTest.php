<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Auth\TwoFactorEnrollmentService;
use App\Application\Auth\TwoFactorVerificationService;
use App\Application\Services\DiagnosticsTelemetryService;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\Ui\UiStepUpController;
use App\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Views\Twig;

final class UiStepUpFlowTest extends TestCase
{
    private UiStepUpController $uiController;
    private StepUpService&MockObject $stepUpServiceMock;
    private TotpServiceInterface&MockObject $totpServiceMock;
    private DiagnosticsTelemetryService&MockObject $telemetryServiceMock;
    private Twig&MockObject $viewMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stepUpServiceMock = $this->createMock(StepUpService::class);
        $this->totpServiceMock = $this->createMock(TotpServiceInterface::class);
        $this->telemetryServiceMock = $this->createMock(DiagnosticsTelemetryService::class);
        $this->viewMock = $this->createMock(Twig::class);

        $enrollmentService = new TwoFactorEnrollmentService(
            $this->stepUpServiceMock,
            $this->totpServiceMock,
            $this->telemetryServiceMock
        );

        $verificationService = new TwoFactorVerificationService(
            $this->stepUpServiceMock,
            $this->telemetryServiceMock
        );

        $webController = new TwoFactorController(
            $enrollmentService,
            $verificationService,
            $this->viewMock
        );

        $this->uiController = new UiStepUpController($webController);
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

    public function test_scoped_verification_success_via_ui_controller(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
                'scope' => 'security',
                'return_to' => '/admins/create',
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

        $response = $this->uiController->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admins/create', $response->getHeaderLine('Location'));
    }

    public function test_invalid_scope_fallback_via_ui_controller(): void
    {
        $request = $this->createAuthenticatedRequest('POST', '/2fa/verify')
            ->withParsedBody([
                'code' => '123456',
                'scope' => 'invalid_scope_val',
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

        $response = $this->uiController->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    public function test_missing_scope_via_ui_controller(): void
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

        $response = $this->uiController->doVerify($request, $response);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
    }
}
