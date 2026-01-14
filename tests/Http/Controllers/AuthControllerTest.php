<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\Service\AdminAuthenticationService;
use App\Http\Controllers\AuthController;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class AuthControllerTest extends TestCase
{
    public function testLoginSuccessLogsActivityWithNewContext(): void
    {
        $authService = $this->createMock(AdminAuthenticationService::class);
        $cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);
        $validationGuard = $this->createMock(ValidationGuard::class);
        $adminActivityLogService = $this->createMock(AdminActivityLogService::class);

        $controller = new AuthController(
            $authService,
            $cryptoService,
            $validationGuard,
            $adminActivityLogService
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Setup Request Data
        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'password' => 'secret'
        ]);

        // Setup Request Context Attribute
        $requestContext = new RequestContext('req-123', '127.0.0.1', 'PHPUnit');
        $request->method('getAttribute')
            ->with(RequestContext::class)
            ->willReturn($requestContext);

        // Setup Auth Service Success
        $loginResult = new AdminLoginResultDTO(123, 'token-xyz');
        $authService->method('login')->willReturn($loginResult);

        // EXPECTATION: Activity Log called with correct contexts and 6 arguments
        $adminActivityLogService->expects($this->once())
            ->method('log')
            ->with(
                $this->callback(function (AdminContext $ctx) {
                    return $ctx->adminId === 123;
                }),
                $this->callback(function (RequestContext $ctx) {
                    return $ctx->requestId === 'req-123';
                }),
                AdminActivityAction::LOGIN_SUCCESS,
                null, // entityType
                null, // entityId
                $this->anything() // metadata
            );

        $controller->login($request, $response);
    }
}
