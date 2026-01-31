<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Logging;

use Maatify\AdminKernel\Application\Contracts\BehaviorTraceRecorderInterface;
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;

class BehaviorTraceMaatifyAdapter implements BehaviorTraceRecorderInterface
{
    public function __construct(
        private BehaviorTraceRecorder $recorder
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $action,
        string $actorType,
        ?int $actorId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        $this->recorder->record(
            action: $action,
            actorType: $actorType,
            actorId: $actorId,
            entityType: $entityType,
            entityId: $entityId,
            correlationId: null,
            requestId: null,
            routeName: null,
            ipAddress: null,
            userAgent: null,
            metadata: $metadata
        );
    }
}
