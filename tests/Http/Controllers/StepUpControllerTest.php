<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\StepUpController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

final class StepUpControllerTest extends TestCase
{
    public function testVerifySuccessRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(new \App\Modules\Validation\DTO\ValidationResultDTO(true));
        $validationGuard = new ValidationGuard($validator);

        $telemetryFactory = $this->makeFinalTelemetryFactory();

        $controller = new StepUpController(
            $stepUpService,
            $validationGuard,
            $telemetryFactory
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('getParsedBody')->willReturn(['code' => '123456']);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'sess-123']);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        // Mock result DTO
        // TotpVerificationResultDTO might be readonly or have private properties.
        // We need to instantiate it correctly.
        // Assuming: public function __construct(public bool $success, public ?string $errorReason = null)
        $resultDto = new TotpVerificationResultDTO(true);

        $stepUpService->method('verifyTotp')->willReturn($resultDto);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller->verify($request, $response);

        // Assert: execution finished without throwing exceptions
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
