<?php

declare(strict_types=1);

namespace App\Domain\ActivityLog\Recorder;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use DateTimeImmutable;

interface CanonicalActivityRecorderInterface
{
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
    ): void;
}
