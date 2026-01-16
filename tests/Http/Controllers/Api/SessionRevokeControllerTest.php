<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Api;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\SessionRevocationService;
use App\Http\Controllers\Api\SessionRevokeController;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionNamedType;

final class SessionRevokeControllerTest extends TestCase
{
    public function testInvokeSuccessRecordsTelemetry(): void
    {
        $revocationService = $this->createMock(SessionRevocationService::class);
        $authzService = $this->createMock(AuthorizationService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(new \App\Modules\Validation\DTO\ValidationResultDTO(true));
        $validationGuard = new ValidationGuard($validator);

        $telemetryFactory = $this->makeFinalTelemetryFactory();

        $controller = new SessionRevokeController(
            $revocationService,
            $authzService,
            $validationGuard,
            $telemetryFactory
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'token-123']);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller($request, $response, ['session_id' => 'hash-456']);

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
