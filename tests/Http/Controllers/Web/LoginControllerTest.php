<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Exception\MustChangePasswordException;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use App\Http\Controllers\Web\LoginController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class LoginControllerTest extends TestCase
{
    private AdminAuthenticationService&MockObject $authService;
    private AdminSessionValidationRepositoryInterface&MockObject $sessionRepo;
    private RememberMeService&MockObject $rememberMe;
    private AdminIdentifierCryptoServiceInterface&MockObject $cryptoService;
    private Twig&MockObject $view;
    private LoginController $controller;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AdminAuthenticationService::class);
        $this->sessionRepo = $this->createMock(AdminSessionValidationRepositoryInterface::class);
        $this->rememberMe = $this->createMock(RememberMeService::class);
        $this->cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);
        $this->view = $this->createMock(Twig::class);

        $this->controller = new LoginController(
            $this->authService,
            $this->sessionRepo,
            $this->rememberMe,
            $this->cryptoService,
            $this->view
        );
    }

    public function test_login_redirects_to_change_password_on_must_change_password_exception(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $email = 'admin@example.com';
        $request->method('getParsedBody')->willReturn([
            'email' => $email,
            'password' => 'secret'
        ]);
        $request->method('getAttribute')->willReturnMap([
            ['template', null, 'login.twig'],
            [RequestContext::class, null, $context],
        ]);

        $this->cryptoService->method('deriveEmailBlindIndex')->with($email)->willReturn('blind_index');

        $this->authService->method('login')
            ->willThrowException(new MustChangePasswordException('Password change required.'));

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/auth/change-password?email=' . urlencode($email))
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        // Assert no other headers (like Set-Cookie) are set
        $response->expects($this->never())
            ->method('withAddedHeader');

        $this->controller->login($request, $response);
    }
}
