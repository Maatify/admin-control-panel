<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
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
        private string $storagePath
    ) {
    }

    public function isLocked(): bool
    {
        // 1. Check if manually/persistently locked
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
        if (($_ENV['RECOVERY_MODE'] ?? 'false') === 'true') {
            return true;
        }

        $key = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
        // Basic length check for security
        if (empty($key) || strlen($key) < 32) {
            return true;
        }

        return false;
    }

    public function getSystemState(): string
    {
        return $this->isLocked() ? self::SYSTEM_STATE_RECOVERY_LOCKED : self::SYSTEM_STATE_ACTIVE;
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
        // If Stored is ACTIVE but ENV is LOCKED -> Must Enter Recovery
        // If Stored is LOCKED but ENV is ACTIVE -> Must Exit Recovery (if we assume Auto-Recovery)
        // NOTE: Strictly following "Single Transition Authority".

        if ($storedState === self::SYSTEM_STATE_ACTIVE && $isEnvLocked) {
            // Reason derivation
            $reason = RecoveryTransitionReason::ENVIRONMENT_OVERRIDE;
            $key = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
            if (empty($key) || strlen($key) < 32) {
                $reason = RecoveryTransitionReason::WEAK_CRYPTO_KEY;
            }

            $this->enterRecovery($reason, 0); // System Actor
        } elseif ($storedState === self::SYSTEM_STATE_RECOVERY_LOCKED && !$isEnvLocked) {
            // Environment cleared, auto-exit recovery
            $this->exitRecovery(RecoveryTransitionReason::ENVIRONMENT_OVERRIDE, 0);
        }
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
        // Optimization: if already in state, do nothing?
        // But file check is cheap.
        // However, if we are calling this, we probably want to enforce the transition event.
        // But monitorState calls it only on change.
        // If manual call repeats the state, maybe we should log it anyway (Idempotent call vs Event)?
        // Prompt says "Canonical Event".
        // If I call enterRecovery and I am already locked, is it a new event?
        // Yes, "Re-entering" or "Confirming" lock.
        // But for strictness, let's allow it. It provides audit trail of the attempt.

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
        if (!file_exists($this->storagePath)) {
            return self::SYSTEM_STATE_ACTIVE;
        }

        $content = file_get_contents($this->storagePath);
        if ($content === false) {
             throw new RuntimeException("Unable to read recovery state file.");
        }

        $state = trim($content);
        if (!in_array($state, [self::SYSTEM_STATE_ACTIVE, self::SYSTEM_STATE_RECOVERY_LOCKED], true)) {
            return 'UNKNOWN';
        }

        return $state;
    }

    private function writeStoredState(string $state): void
    {
        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                 throw new RuntimeException("Unable to create recovery state directory.");
            }
        }

        if (file_put_contents($this->storagePath, $state) === false) {
            throw new RuntimeException("Unable to write recovery state file.");
        }
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
