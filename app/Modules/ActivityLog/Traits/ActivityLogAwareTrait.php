<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:04
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\Traits;

use App\Domain\ActivityLog\Recorder\ActivityRecorder;
use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use DateTimeImmutable;

trait ActivityLogAwareTrait
{
    protected ActivityRecorder $activityRecorder;

    public function setActivityLogService(ActivityRecorder $activityRecorder): void
    {
        $this->activityRecorder = $activityRecorder;
    }

    /**
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
    protected function logActivity(
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
        $this->activityRecorder->log(
            action    : $action,
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
    }
}
