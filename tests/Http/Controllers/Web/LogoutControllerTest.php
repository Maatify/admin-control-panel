<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Services\DiagnosticsTelemetryService;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use App\Http\Controllers\Web\LogoutController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class LogoutControllerTest extends TestCase
{
    public function testLogoutSuccessRecordsTelemetry(): void
    {
        $sessionRepo = $this->createMock(AdminSessionValidationRepositoryInterface::class);
        $rememberMe = $this->createMock(RememberMeService::class);
        $securityLogger = $this->createMock(SecurityEventLoggerInterface::class);
        $authService = $this->createMock(AdminAuthenticationService::class);
        $telemetryService = $this->createMock(DiagnosticsTelemetryService::class);

        $telemetryService->expects($this->once())
            ->method('recordEvent')
            ->with(
                'resource_mutation',
                'INFO',
                'ADMIN',
                123,
                $this->callback(function($metadata) {
                    return isset($metadata['action']) && $metadata['action'] === 'self_logout';
                })
            );

        $controller = new LogoutController(
            $sessionRepo,
            $rememberMe,
            $securityLogger,
            $authService,
            $telemetryService
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'token-123']);
        $request->method('getUri')->willReturn($uri);
        $uri->method('getScheme')->willReturn('https');

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $response->method('withAddedHeader')->willReturn($response);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller->logout($request, $response);
    }
}
