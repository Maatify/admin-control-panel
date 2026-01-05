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
        private PDO $pdo
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
                $actorId,
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
