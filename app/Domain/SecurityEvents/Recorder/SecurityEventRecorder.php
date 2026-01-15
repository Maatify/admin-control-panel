<?php

declare(strict_types=1);

namespace App\Domain\SecurityEvents\Recorder;

use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Modules\SecurityEvents\Contracts\SecurityEventLoggerInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use App\Modules\SecurityEvents\Exceptions\SecurityEventStorageException;

/**
 * Domain-level security event recorder.
 *
 * - Transforms Domain DTO → Module DTO
 * - Best-effort delegation
 * - MUST NOT break main flow
 */
final readonly class SecurityEventRecorder implements SecurityEventRecorderInterface
{
    public function __construct(
        private SecurityEventLoggerInterface $logger
    ) {
    }

    public function record(SecurityEventRecordDTO $event): void
    {
        $dto = new SecurityEventDTO(
            actorType: $event->actorType->value,
            actorId  : $event->actorId,

            eventType: $event->eventType,
            severity : $event->severity,

            requestId: $event->requestId,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
            routeName: $event->routeName,

            metadata : $event->metadata
        // occurredAt intentionally omitted → infra sets it
        );

        try {
            $this->logger->log($dto);
        } catch (SecurityEventStorageException) {
            // Best-effort silence
        }
    }
}
