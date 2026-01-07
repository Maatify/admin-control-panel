<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Enum\RecoveryTransitionReason;
use App\Domain\Exception\RecoveryLockException;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class RecoveryStateService
{
    public const SYSTEM_STATE_ACTIVE = 'ACTIVE';
    public const SYSTEM_STATE_RECOVERY_LOCKED = 'RECOVERY_LOCKED';

    // Actions
    public const ACTION_LOGIN = 'login';
    public const ACTION_OTP_VERIFY = 'otp_verify';
    public const ACTION_OTP_RESEND = 'otp_resend';
    public const ACTION_STEP_UP = 'step_up';
    public const ACTION_ROLE_ASSIGNMENT = 'role_assignment';
    public const ACTION_PERMISSION_CHANGE = 'permission_change';

    private const BLOCKED_ACTIONS = [
        self::ACTION_LOGIN,
        self::ACTION_OTP_VERIFY,
        self::ACTION_OTP_RESEND,
        self::ACTION_STEP_UP,
        self::ACTION_ROLE_ASSIGNMENT,
        self::ACTION_PERMISSION_CHANGE,
    ];

    public function __construct(
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private SecurityEventLoggerInterface $securityLogger,
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {
    }

    public function isLocked(): bool
    {
        // 1. Check if persistently locked in DB
        if ($this->readStoredState() === self::SYSTEM_STATE_RECOVERY_LOCKED) {
            return true;
        }

        // 2. Check Environment (Fail-Safe)
        if ($this->isEnvLocked()) {
            return true;
        }

        return false;
    }

    private function isEnvLocked(): bool
    {
        if ($this->config->isRecoveryMode) {
            return true;
        }

        $key = $this->config->emailBlindIndexKey;
        // Basic length check for security
        if (empty($key) || strlen($key) < 32) {
            return true;
        }

        return false;
    }

    public function enforce(string $action, ?int $actorId = null): void
    {
        if (!$this->isLocked()) {
            return;
        }

        if (in_array($action, self::BLOCKED_ACTIONS, true)) {
            $this->handleBlockedAction($action, $actorId);
        }
    }

    public function monitorState(): void
    {
        $storedState = $this->readStoredState();
        $isEnvLocked = $this->isEnvLocked();

        // Calculate expected state based on Environment only (for monitoring automated transitions)
        // STRICT: Only Auto-Lock if Env dictates. Never Auto-Unlock.
        // If Stored is ACTIVE but ENV is LOCKED -> Must Enter Recovery
        if ($storedState === self::SYSTEM_STATE_ACTIVE && $isEnvLocked) {
            // Reason derivation
            $reason = RecoveryTransitionReason::ENVIRONMENT_OVERRIDE;
            $key = $this->config->emailBlindIndexKey;
            if (empty($key) || strlen($key) < 32) {
                $reason = RecoveryTransitionReason::WEAK_CRYPTO_KEY;
            }

            $this->enterRecovery($reason, 0); // System Actor
        }

        // Auto-Unlock is FORBIDDEN.
        // If Stored is LOCKED but Env is ACTIVE -> Remain LOCKED until explicit manual exit.
    }

    public function enterRecovery(RecoveryTransitionReason $reason, int $actorId): void
    {
        $this->performTransition(
            self::SYSTEM_STATE_RECOVERY_LOCKED,
            'recovery_entered',
            $reason,
            $actorId
        );
    }

    public function exitRecovery(RecoveryTransitionReason $reason, int $actorId): void
    {
        // Prevent manual exit if Environment enforces lock
        if ($this->isEnvLocked()) {
            throw new RuntimeException("Cannot exit recovery: Environment configuration enforces lock.");
        }

        $this->performTransition(
            self::SYSTEM_STATE_ACTIVE,
            'recovery_exited',
            $reason,
            $actorId
        );
    }

    private function performTransition(
        string $targetState,
        string $eventType,
        RecoveryTransitionReason $reason,
        int $actorId
    ): void {
        $txStarted = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $txStarted = true;
        }

        try {
            // 1. Write Authoritative Audit
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                $eventType,
                'system', // Target Type
                0,        // Target ID (System)
                'CRITICAL',
                [
                    'reason' => $reason->value,
                    'target_state' => $targetState
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            // 2. Update Persistent State
            $this->writeStoredState($targetState);

            if ($txStarted) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($txStarted) {
                $this->pdo->rollBack();
            }
            throw new RuntimeException("Failed to persist recovery transition ({$eventType}): " . $e->getMessage(), 0, $e);
        }
    }

    private function readStoredState(): string
    {
        // Read from DB `system_state`
        $stmt = $this->pdo->prepare("SELECT state_value FROM system_state WHERE state_key = 'recovery_mode'");
        $stmt->execute();
        $result = $stmt->fetchColumn();

        if ($result === false) {
             // Default to ACTIVE if not found (Bootstrap scenario)
             return self::SYSTEM_STATE_ACTIVE;
        }

        return (string)$result;
    }

    private function writeStoredState(string $state): void
    {
        // Write to DB `system_state`
        // Upsert
        $sql = "INSERT INTO system_state (state_key, state_value, updated_at)
                VALUES ('recovery_mode', :val, NOW())
                ON DUPLICATE KEY UPDATE state_value = :val, updated_at = NOW()";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':val', $state);
        $stmt->execute();
    }

    private function handleBlockedAction(string $action, ?int $actorId): void
    {
        // 1. Emit Security Event
        try {
            $this->securityLogger->log(new SecurityEventDTO(
                $actorId,
                'recovery_action_blocked',
                'critical',
                ['action' => $action, 'reason' => 'recovery_locked_mode'],
                '0.0.0.0',
                'system',
                new DateTimeImmutable()
            ));
        } catch (\Throwable $e) {
            // Best effort
        }

        // 2. Write Authoritative Audit Event (Transactional)
        $txStarted = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $txStarted = true;
        }

        try {
            $this->auditWriter->write(new AuditEventDTO(
                $actorId ?? 0,
                'recovery_action_blocked',
                'system',
                null,
                'CRITICAL',
                ['attempted_action' => $action, 'reason' => 'recovery_locked_mode'],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            if ($txStarted) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($txStarted) {
                $this->pdo->rollBack();
            }
        }

        // 3. Throw Exception
        throw new RecoveryLockException("Action '$action' blocked by Recovery-Locked Mode.");
    }
}
