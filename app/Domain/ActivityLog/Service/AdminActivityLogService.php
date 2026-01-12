<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\ActivityLog\Service;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use App\Modules\ActivityLog\Service\ActivityLogService;

final readonly class AdminActivityLogService
{
    private const ACTOR_TYPE = 'admin';

    public function __construct(
        private ActivityLogService $service,
    )
    {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function log(
        AdminContext $adminContext,
        RequestContext $requestContext,
        ActivityActionInterface|string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void
    {
        $this->service->log(
            action: $action,
            actorType: self::ACTOR_TYPE,
            actorId: $adminContext->adminId,
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
            ipAddress: $requestContext->ipAddress,
            userAgent: $requestContext->userAgent,
            requestId: $requestContext->requestId
        );
    }
}
