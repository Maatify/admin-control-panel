<?php

declare(strict_types=1);

namespace App\Domain\ActivityLog\Recorder;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use DateTimeImmutable;
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;

final readonly class CanonicalActivityRecorder implements CanonicalActivityRecorderInterface
{
    public function __construct(
        private BehaviorTraceRecorder $recorder
    ) {
    }

    /**
     * Log an activity event.
     *
     * @param   ActivityActionInterface|string  $action
     * @param   string                          $actorType
     * @param   int|null                        $actorId
     * @param   string|null                     $entityType
     * @param   int|null                        $entityId
     * @param   array<string, mixed>|null       $metadata
     * @param   string|null                     $ipAddress
     * @param   string|null                     $userAgent
     * @param   string|null                     $requestId
     * @param   DateTimeImmutable|null          $occurredAt  (Ignored by canonical recorder, uses system clock)
     */
    public function log(
        ActivityActionInterface|string $action,

        string $actorType,
        ?int $actorId,

        ?string $entityType = null,
        ?int $entityId = null,

        ?array $metadata = null,

        ?string $ipAddress = null,
        ?string $userAgent = null,

        ?string $requestId = null,

        ?DateTimeImmutable $occurredAt = null,
    ): void {
        // Resolve action string
        $actionValue = $action instanceof ActivityActionInterface
            ? $action->toString()
            : $action;

        $this->recorder->record(
            action: $actionValue,
            actorType: $actorType,
            actorId: $actorId,
            entityType: $entityType,
            entityId: $entityId,
            correlationId: null, // Legacy doesn't support correlationId explicitly
            requestId: $requestId,
            routeName: null, // Legacy doesn't support routeName
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata
        );
    }
}
