<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Telemetry\Recorder;

use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use App\Domain\Telemetry\Recorder\TelemetryRecorder;
use App\Modules\Telemetry\Contracts\TelemetryLoggerInterface;
use App\Modules\Telemetry\DTO\TelemetryEventDTO;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use App\Modules\Telemetry\Exceptions\TelemetryStorageException;
use PHPUnit\Framework\TestCase;

final class TelemetryRecorderTest extends TestCase
{
    public function testRecordCallsLogger(): void
    {
        $logger = $this->createMock(TelemetryLoggerInterface::class);
        $recorder = new TelemetryRecorder($logger);

        $dto = new TelemetryRecordDTO(
            actorType: TelemetryActorTypeEnum::SYSTEM,
            actorId: null,
            eventType: TelemetryEventTypeEnum::HTTP_REQUEST_END,
            severity: TelemetrySeverityEnum::INFO,
            requestId: 'req-123',
            routeName: 'home',
            ipAddress: '127.0.0.1',
            userAgent: 'test-agent',
            metadata: ['foo' => 'bar']
        );

        $logger->expects($this->once())
            ->method('log')
            ->with($this->callback(function (TelemetryEventDTO $event) use ($dto) {
                return $event->actorType === $dto->actorType->value
                    && $event->actorId === $dto->actorId
                    && $event->eventType === $dto->eventType
                    && $event->severity === $dto->severity
                    && $event->requestId === $dto->requestId
                    && $event->routeName === $dto->routeName
                    && $event->ipAddress === $dto->ipAddress
                    && $event->userAgent === $dto->userAgent
                    && $event->metadata === $dto->metadata;
            }));

        $recorder->record($dto);
    }

    public function testRecordSwallowsStorageException(): void
    {
        $logger = $this->createMock(TelemetryLoggerInterface::class);
        $recorder = new TelemetryRecorder($logger);

        $dto = new TelemetryRecordDTO(
            actorType: TelemetryActorTypeEnum::SYSTEM,
            actorId: null,
            eventType: TelemetryEventTypeEnum::HTTP_REQUEST_END,
            severity: TelemetrySeverityEnum::INFO
        );

        $logger->method('log')
            ->willThrowException(new TelemetryStorageException('DB error'));

        // Should not throw
        $recorder->record($dto);

        $this->assertTrue(true, 'Exception was swallowed');
    }
}
