<?php

declare(strict_types=1);

namespace Tests\Domain\SecurityEvents;

use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorder;
use App\Modules\SecurityEvents\Contracts\SecurityEventLoggerInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Exceptions\SecurityEventStorageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SecurityEventRecorderTest extends TestCase
{
    private SecurityEventLoggerInterface&MockObject $logger;
    private SecurityEventRecorder $recorder;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(SecurityEventLoggerInterface::class);
        $this->recorder = new SecurityEventRecorder($this->logger);
    }

    public function test_successful_delegation(): void
    {
        $domainDto = new SecurityEventRecordDTO(
            actorType: SecurityEventActorTypeEnum::ADMIN,
            actorId: 123,
            eventType: SecurityEventTypeEnum::LOGIN_FAILED,
            severity: SecurityEventSeverityEnum::WARNING,
            requestId: 'req-1',
            routeName: 'login',
            ipAddress: '127.0.0.1',
            userAgent: 'test-agent',
            metadata: ['foo' => 'bar']
        );

        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->callback(function (SecurityEventDTO $dto) use ($domainDto) {
                return $dto->actorType === $domainDto->actorType->value
                    && $dto->actorId === $domainDto->actorId
                    && $dto->eventType === $domainDto->eventType
                    && $dto->severity === $domainDto->severity
                    && $dto->requestId === $domainDto->requestId
                    && $dto->routeName === $domainDto->routeName
                    && $dto->ipAddress === $domainDto->ipAddress
                    && $dto->userAgent === $domainDto->userAgent
                    && $dto->metadata === $domainDto->metadata;
            }));

        $this->recorder->record($domainDto);
    }

    public function test_best_effort_silence(): void
    {
        $domainDto = new SecurityEventRecordDTO(
            actorType: SecurityEventActorTypeEnum::SYSTEM,
            actorId: null,
            eventType: SecurityEventTypeEnum::SESSION_EXPIRED,
            severity: SecurityEventSeverityEnum::INFO
        );

        $this->logger->expects($this->once())
            ->method('log')
            ->willThrowException(new SecurityEventStorageException('DB Down'));

        // Expect NO exception to be thrown
        $this->recorder->record($domainDto);
    }
}
