<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditRiskLevelEnum;
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;

/**
 * Records Governance and Security Posture changes. This is the Source of Truth for compliance.
 *
 * BEHAVIOR GUARANTEE: FAIL-CLOSED (Transactional)
 * If logging fails, the business transaction MUST roll back.
 */
class AuthoritativeAuditService
{
    private const ACTION_ADMIN_CREATE = 'admin.create';
    private const ACTION_ADMIN_STATUS_CHANGE = 'admin.status_change';
    private const ACTION_ROLE_ASSIGN = 'role.assign';
    private const ACTION_SYSTEM_CONFIG_CHANGE = 'system_config.change';
    private const ACTION_OWNERSHIP_TRANSFER = 'ownership.transfer';

    private const ACTOR_TYPE_ADMIN = 'ADMIN';

    public function __construct(
        private AuthoritativeAuditRecorder $recorder
    ) {
    }

    /**
     * Used when a new privileged account was created.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordAdminCreated(int $initiatorId, int $newAdminId, string $initialRole): void
    {
        $this->recorder->record(
            action: self::ACTION_ADMIN_CREATE,
            targetType: 'admin',
            targetId: $newAdminId,
            riskLevel: AuthoritativeAuditRiskLevelEnum::HIGH,
            actorType: self::ACTOR_TYPE_ADMIN,
            actorId: $initiatorId,
            payload: [
                'initial_role' => $initialRole
            ]
        );
    }

    /**
     * Used when Admin was suspended, banned, or reactivated.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordAdminStatusChanged(int $initiatorId, int $targetAdminId, string $oldStatus, string $newStatus): void
    {
        $this->recorder->record(
            action: self::ACTION_ADMIN_STATUS_CHANGE,
            targetType: 'admin',
            targetId: $targetAdminId,
            riskLevel: AuthoritativeAuditRiskLevelEnum::HIGH,
            actorType: self::ACTOR_TYPE_ADMIN,
            actorId: $initiatorId,
            payload: [
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]
        );
    }

    /**
     * Used when Admin permissions were modified via role change.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordRoleAssigned(int $initiatorId, int $targetAdminId, string $roleName): void
    {
        $this->recorder->record(
            action: self::ACTION_ROLE_ASSIGN,
            targetType: 'admin',
            targetId: $targetAdminId,
            riskLevel: AuthoritativeAuditRiskLevelEnum::CRITICAL,
            actorType: self::ACTOR_TYPE_ADMIN,
            actorId: $initiatorId,
            payload: [
                'role_name' => $roleName
            ]
        );
    }

    /**
     * Used when Global system security configuration was altered.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordSystemConfigChanged(int $initiatorId, string $key, string $oldValue, string $newValue): void
    {
        $this->recorder->record(
            action: self::ACTION_SYSTEM_CONFIG_CHANGE,
            targetType: 'system_config',
            targetId: null, // Global config might not have an ID, or hash of key. Null is acceptable for singleton.
            riskLevel: AuthoritativeAuditRiskLevelEnum::CRITICAL,
            actorType: self::ACTOR_TYPE_ADMIN,
            actorId: $initiatorId,
            payload: [
                'config_key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue
            ]
        );
    }

    /**
     * Used when Ownership of a critical resource was reassigned.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordOwnershipTransferred(int $initiatorId, string $assetType, int $assetId, int $newOwnerId): void
    {
        $this->recorder->record(
            action: self::ACTION_OWNERSHIP_TRANSFER,
            targetType: $assetType,
            targetId: $assetId,
            riskLevel: AuthoritativeAuditRiskLevelEnum::CRITICAL,
            actorType: self::ACTOR_TYPE_ADMIN,
            actorId: $initiatorId,
            payload: [
                'new_owner_id' => $newOwnerId
            ]
        );
    }
}
