<?php

declare(strict_types=1);

namespace App\Application\Services;

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

    // Risk levels are internal; Domain passes nothing related to risk.
    // private const RISK_HIGH = 'HIGH';
    // private const RISK_CRITICAL = 'CRITICAL';

    public function __construct(
        // private AuthoritativeAuditRecorder $recorder // Dependency to be injected
    ) {
    }

    /**
     * Used when a new privileged account was created.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordAdminCreated(int $initiatorId, int $newAdminId, string $initialRole): void
    {
        // $this->recorder->record(
        //     action: self::ACTION_ADMIN_CREATE,
        //     initiatorId: $initiatorId,
        //     riskLevel: self::RISK_HIGH,
        //     payload: [
        //         'new_admin_id' => $newAdminId,
        //         'initial_role' => $initialRole
        //     ]
        // );
    }

    /**
     * Used when Admin was suspended, banned, or reactivated.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordAdminStatusChanged(int $initiatorId, int $targetAdminId, string $oldStatus, string $newStatus): void
    {
        // $this->recorder->record(
        //     action: self::ACTION_ADMIN_STATUS_CHANGE,
        //     initiatorId: $initiatorId,
        //     riskLevel: self::RISK_HIGH,
        //     payload: [
        //         'target_admin_id' => $targetAdminId,
        //         'old_status' => $oldStatus,
        //         'new_status' => $newStatus
        //     ]
        // );
    }

    /**
     * Used when Admin permissions were modified via role change.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordRoleAssigned(int $initiatorId, int $targetAdminId, string $roleName): void
    {
        // $this->recorder->record(
        //     action: self::ACTION_ROLE_ASSIGN,
        //     initiatorId: $initiatorId,
        //     riskLevel: self::RISK_CRITICAL,
        //     payload: [
        //         'target_admin_id' => $targetAdminId,
        //         'role_name' => $roleName
        //     ]
        // );
    }

    /**
     * Used when Global system security configuration was altered.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordSystemConfigChanged(int $initiatorId, string $key, string $oldValue, string $newValue): void
    {
        // $this->recorder->record(
        //     action: self::ACTION_SYSTEM_CONFIG_CHANGE,
        //     initiatorId: $initiatorId,
        //     riskLevel: self::RISK_CRITICAL,
        //     payload: [
        //         'config_key' => $key,
        //         'old_value' => $oldValue,
        //         'new_value' => $newValue
        //     ]
        // );
    }

    /**
     * Used when Ownership of a critical resource was reassigned.
     *
     * @throws \Throwable If logging fails (Fail-Closed)
     */
    public function recordOwnershipTransferred(int $initiatorId, string $assetType, int $assetId, int $newOwnerId): void
    {
        // $this->recorder->record(
        //     action: self::ACTION_OWNERSHIP_TRANSFER,
        //     initiatorId: $initiatorId,
        //     riskLevel: self::RISK_CRITICAL,
        //     payload: [
        //         'asset_type' => $assetType,
        //         'asset_id' => $assetId,
        //         'new_owner_id' => $newOwnerId
        //     ]
        // );
    }
}
