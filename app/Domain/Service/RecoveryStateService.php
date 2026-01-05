<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
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
        $currentState = $this->getSystemState();
        $storedState = $this->readStoredState();

        if ($currentState !== $storedState) {
            $this->handleTransition($storedState, $currentState);
        }
    }

    private function readStoredState(): string
    {
        if (!file_exists($this->storagePath)) {
            // Default assumption if file missing: ACTIVE
            // This handles initial boot or fresh install gracefully
            return self::SYSTEM_STATE_ACTIVE;
        }

        $content = file_get_contents($this->storagePath);
        if ($content === false) {
             // If we can't read, fail closed? Or assume ACTIVE?
             // If we can't read, we can't monitor transitions reliably.
             // But crashing on every request due to file permission is harsh.
             // However, strictly, "NO silent state".
             // We'll throw.
             throw new RuntimeException("Unable to read recovery state file.");
        }

        $state = trim($content);
        // Basic validation
        if (!in_array($state, [self::SYSTEM_STATE_ACTIVE, self::SYSTEM_STATE_RECOVERY_LOCKED], true)) {
            // Corrupt file? Assume ACTIVE to trigger transition to current if needed, or throw?
            // If corrupt, we should probably reset/detect.
            // Let's return UNKNOWN to force a transition log if current is valid.
            return 'UNKNOWN';
        }

        return $state;
    }

    private function writeStoredState(string $state): void
    {
        // Ensure directory exists
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

    private function handleTransition(string $previousState, string $currentState): void
    {
        // Determine action name
        if ($currentState === self::SYSTEM_STATE_RECOVERY_LOCKED) {
            $action = 'recovery_entered';
        } elseif ($currentState === self::SYSTEM_STATE_ACTIVE) {
            $action = 'recovery_exited';
        } else {
            $action = 'recovery_state_changed'; // Fallback
        }

        $txStarted = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $txStarted = true;
        }

        try {
            $this->auditWriter->write(new AuditEventDTO(
                0, // System actor (0)
                $action,
                'system',
                0, // System target
                'CRITICAL',
                [
                    'reason' => 'environment_variable_change',
                    'previous_state' => $previousState,
                    'current_state' => $currentState
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->writeStoredState($currentState);

            if ($txStarted) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($txStarted) {
                $this->pdo->rollBack();
            }
            // If we failed to write audit or file, we MUST throw to prevent silent failure
            throw new RuntimeException("Failed to persist recovery state transition: " . $e->getMessage(), 0, $e);
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
                '0.0.0.0', // Context limited here unless passed
                'system',
                new DateTimeImmutable()
            ));
        } catch (\Throwable $e) {
            // Ignore failure to log security event to avoid loops, but we must try.
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
            // If audit fails, we still need to block
        }

        // 3. Throw Exception
        throw new RecoveryLockException("Action '$action' blocked by Recovery-Locked Mode.");
    }
}
