<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\BehaviorTraceRecorderInterface;
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
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $requestId = null
    ): void {
        $this->recorder->record(
            action: $action,
            actorType: $actorType,
            actorId: $actorId,
            entityType: $entityType,
            entityId: $entityId,
            correlationId: null,
            requestId: $requestId,
            routeName: null,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata
        );
    }
}
