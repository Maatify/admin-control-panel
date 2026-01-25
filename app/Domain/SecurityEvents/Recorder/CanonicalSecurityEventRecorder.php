<?php

declare(strict_types=1);

namespace App\Domain\SecurityEvents\Recorder;

use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder;

final readonly class CanonicalSecurityEventRecorder implements SecurityEventRecorderInterface
{
    public function __construct(
        private SecuritySignalsRecorder $recorder
    ) {
    }

    public function record(SecurityEventRecordDTO $event): void
    {
        $this->recorder->record(
            signalType: $event->eventType->value,
            severity: $event->severity->value,
            actorType: $event->actorType->value,
            actorId: $event->actorId,
            metadata: $event->metadata,
            correlationId: null, // Not present in Domain DTO
            requestId: $event->requestId,
            routeName: $event->routeName,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent
        );
    }
}
