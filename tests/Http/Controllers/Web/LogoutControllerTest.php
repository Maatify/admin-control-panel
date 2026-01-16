<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use App\Http\Controllers\Web\LogoutController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Tests\Support\TelemetryTestHelper;

final class LogoutControllerTest extends TestCase
{
    public function testLogoutSuccessRecordsTelemetry(): void
    {
        $sessionRepo = $this->createMock(AdminSessionValidationRepositoryInterface::class);
        $rememberMe = $this->createMock(RememberMeService::class);
        $securityLogger = $this->createMock(SecurityEventLoggerInterface::class);
        $authService = $this->createMock(AdminAuthenticationService::class);

        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $telemetryFactory = $helper['factory'];
        $spy = $helper['recorder'];

        $controller = new LogoutController(
            $sessionRepo,
            $rememberMe,
            $securityLogger,
            $authService,
            $telemetryFactory
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

        // Assert
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryEventTypeEnum::RESOURCE_MUTATION, $spy->records[0]->eventType);
        $this->assertEquals('self_logout', $spy->records[0]->metadata['action']);
    }
}
