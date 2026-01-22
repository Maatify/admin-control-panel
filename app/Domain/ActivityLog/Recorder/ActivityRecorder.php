<?php

declare(strict_types=1);

namespace App\Domain\ActivityLog\Recorder;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Exceptions\ActivityLogStorageException;
use DateTimeImmutable;

final readonly class ActivityRecorder
{
    public function __construct(
        private ActivityLogWriterInterface $writer,
    )
    {
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
     * @param   DateTimeImmutable|null          $occurredAt
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
    ): void
    {
        // Resolve action string
        $actionValue = $action instanceof ActivityActionInterface
            ? $action->toString()
            : $action;

        // Enrich timestamp if missing
        $occurredAt ??= new DateTimeImmutable();

        $dto = new ActivityLogDTO(
            action    : $actionValue,

            actorType : $actorType,
            actorId   : $actorId,

            entityType: $entityType,
            entityId  : $entityId,

            metadata  : $metadata,

            ipAddress : $ipAddress,
            userAgent : $userAgent,

            requestId : $requestId,

            occurredAt: $occurredAt,
        );

        try {
            $this->writer->write($dto);
        } catch (ActivityLogStorageException) {
            // Best-effort policy: Swallow storage exceptions
            // Activity logging failure MUST NOT break the main application flow
        }
    }
}
