<?php

declare(strict_types=1);

namespace Tests\Modules\SecuritySignals;

use Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface;
use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;
use Maatify\SecuritySignals\Exception\SecuritySignalsStorageException;
use Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder;
use Maatify\SecuritySignals\Services\ClockInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SecuritySignalsRecorderTest extends TestCase
{
    public function test_records_signal_correctly(): void
    {
        $logger = $this->createMock(SecuritySignalsLoggerInterface::class);
        $clock = $this->createMock(ClockInterface::class);
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $clock->method('now')->willReturn($now);

        $recorder = new SecuritySignalsRecorder($logger, $clock);

        $logger->expects($this->once())
            ->method('write')
            ->with($this->callback(function (SecuritySignalRecordDTO $dto) use ($now) {
                return $dto->signalType === 'login_failed'
                    && $dto->severity === 'WARNING'
                    && $dto->actorType === 'ANONYMOUS'
                    && $dto->occurredAt === $now;
            }));

        $recorder->record(
            signalType: 'login_failed',
            severity: SecuritySignalSeverityEnum::WARNING,
            actorType: SecuritySignalActorTypeEnum::ANONYMOUS,
            actorId: null
        );
    }

    public function test_swallows_storage_exception(): void
    {
        $logger = $this->createMock(SecuritySignalsLoggerInterface::class);
        $logger->method('write')->willThrowException(new SecuritySignalsStorageException('DB error'));

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable());

        $recorder = new SecuritySignalsRecorder($logger, $clock);

        // Should not throw
        $recorder->record(
            signalType: 'test',
            severity: 'INFO',
            actorType: 'SYSTEM',
            actorId: 1
        );

        $this->assertTrue(true); // Reached here
    }
}
