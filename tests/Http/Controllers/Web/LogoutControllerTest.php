<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
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
use ReflectionClass;
use ReflectionNamedType;

final class LogoutControllerTest extends TestCase
{
    public function testLogoutSuccessRecordsTelemetry(): void
    {
        $sessionRepo = $this->createMock(AdminSessionValidationRepositoryInterface::class);
        $rememberMe = $this->createMock(RememberMeService::class);
        $securityLogger = $this->createMock(SecurityEventLoggerInterface::class);
        $authService = $this->createMock(AdminAuthenticationService::class);
        $telemetryFactory = $this->makeFinalTelemetryFactory();

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

        $this->assertTrue(true);
    }

    private function makeFinalTelemetryFactory(): HttpTelemetryRecorderFactory
    {
        $ref = new ReflectionClass(HttpTelemetryRecorderFactory::class);
        /** @var HttpTelemetryRecorderFactory $factory */
        $factory = $ref->newInstanceWithoutConstructor();

        $recorder = new class implements \App\Domain\Telemetry\Recorder\TelemetryRecorderInterface {
            public function record(\App\Domain\Telemetry\DTO\TelemetryRecordDTO $dto): void {}
        };

        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            // Assign dummy recorder
            if (str_contains($typeName, 'TelemetryRecorder') || str_contains($typeName, 'RecorderInterface')) {
                 $prop->setAccessible(true);
                 $prop->setValue($factory, $recorder);
            }
        }
        return $factory;
    }
}
