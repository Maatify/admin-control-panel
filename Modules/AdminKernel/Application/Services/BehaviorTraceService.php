<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Services;

use Maatify\AdminKernel\Application\Contracts\BehaviorTraceRecorderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Tracks operational state changes (mutations) and user journey steps.
 * Maps to the **Operational Activity** domain.
 *
 * BEHAVIOR GUARANTEE: FAIL-OPEN (Best Effort)
 * Operational logging MUST NOT cause the business operation to fail.
 */
class BehaviorTraceService
{
    private const ACTION_ENTITY_CREATE = 'entity.create';
    private const ACTION_ENTITY_UPDATE = 'entity.update';
    private const ACTION_ENTITY_DELETE = 'entity.delete';
    private const ACTION_EXECUTE = 'action.execute';
    private const ACTION_BULK_EXECUTE = 'bulk_action.execute';

    private const ACTOR_TYPE_ADMIN = 'ADMIN';

    public function __construct(
        private LoggerInterface $logger,
        private BehaviorTraceRecorderInterface $recorder
    ) {
    }

    /**
     * Used when a new standard resource was created.
     */
    public function recordEntityCreated(int $adminId, string $entityType, string $entityId): void
    {
        try {
            $this->recorder->record(
                action: self::ACTION_ENTITY_CREATE,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $entityType,
                entityId: (int)$entityId,
                metadata: ['type' => $entityType, 'id' => $entityId]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordEntityCreated', $e);
        }
    }

    /**
     * Used when an existing resource was modified.
     */
    public function recordEntityUpdated(int $adminId, string $entityType, string $entityId): void
    {
        try {
            $this->recorder->record(
                action: self::ACTION_ENTITY_UPDATE,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $entityType,
                entityId: (int)$entityId,
                metadata: ['type' => $entityType, 'id' => $entityId]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordEntityUpdated', $e);
        }
    }

    /**
     * Used when a resource was removed.
     */
    public function recordEntityDeleted(int $adminId, string $entityType, string $entityId): void
    {
        try {
            $this->recorder->record(
                action: self::ACTION_ENTITY_DELETE,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $entityType,
                entityId: (int)$entityId,
                metadata: ['type' => $entityType, 'id' => $entityId]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordEntityDeleted', $e);
        }
    }

    /**
     * Used when a specific named business action was performed (e.g., "approve_request", "resend_invite").
     */
    public function recordActionExecuted(int $adminId, string $actionName, string $targetType, string $targetId): void
    {
        try {
            $this->recorder->record(
                action: self::ACTION_EXECUTE,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $targetType,
                entityId: (int)$targetId,
                metadata: [
                    'action_name' => $actionName,
                    'target_type' => $targetType,
                    'target_id' => $targetId
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordActionExecuted', $e);
        }
    }

    /**
     * Used when a bulk operation affected multiple items.
     */
    public function recordBulkActionExecuted(int $adminId, string $actionName, string $entityType, int $count): void
    {
        try {
            $this->recorder->record(
                action: self::ACTION_BULK_EXECUTE,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $entityType,
                metadata: [
                    'action_name' => $actionName,
                    'count' => $count
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordBulkActionExecuted', $e);
        }
    }

    private function logFailure(string $method, Throwable $e): void
    {
        $this->logger->error(
            sprintf('[BehaviorTraceService] %s failed: %s', $method, $e->getMessage()),
            ['exception' => $e]
        );
    }
}
