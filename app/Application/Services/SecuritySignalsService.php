<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\SecuritySignalsRecorderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Captures authentication, authorization, and security policy anomalies.
 *
 * BEHAVIOR GUARANTEE: FAIL-OPEN (Best Effort)
 * Failures in logging MUST NOT block the user flow or cause a system crash.
 */
class SecuritySignalsService
{
    private const SIGNAL_LOGIN_SUCCESS = 'login_success';
    private const SIGNAL_LOGIN_FAILED = 'login_failed';
    private const SIGNAL_ACCESS_DENIED = 'access_denied';
    private const SIGNAL_STEP_UP_FAILED = 'step_up_failed';
    private const SIGNAL_SESSION_TERMINATED = 'session_terminated';

    private const SEVERITY_INFO = 'INFO';
    private const SEVERITY_WARNING = 'WARNING';

    private const ACTOR_TYPE_ADMIN = 'ADMIN';
    private const ACTOR_TYPE_ANONYMOUS = 'ANONYMOUS';

    public function __construct(
        private LoggerInterface $logger,
        private SecuritySignalsRecorderInterface $recorder
    ) {
    }

    /**
     * Used when an administrator successfully authenticates.
     */
    public function recordLoginSuccess(int $adminId, string $ipAddress, string $userAgent): void
    {
        try {
            $this->recorder->record(
                signalType: self::SIGNAL_LOGIN_SUCCESS,
                severity: self::SEVERITY_INFO,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                ipAddress: $ipAddress,
                userAgent: $userAgent
            );
        } catch (Throwable $e) {
            $this->logFailure('recordLoginSuccess', $e);
        }
    }

    /**
     * Used when authentication fails (bad password, user not found).
     */
    public function recordLoginFailed(string $inputIdentifier, string $ipAddress, string $userAgent, string $reason): void
    {
        try {
            $this->recorder->record(
                signalType: self::SIGNAL_LOGIN_FAILED,
                severity: self::SEVERITY_WARNING,
                actorType: self::ACTOR_TYPE_ANONYMOUS,
                actorId: null,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                metadata: [
                    'identifier' => $inputIdentifier,
                    'reason' => $reason
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordLoginFailed', $e);
        }
    }

    /**
     * Used when an authenticated admin is blocked from an action by the authorization policy.
     */
    public function recordAccessDenied(int $adminId, string $resource, string $action, string $ipAddress): void
    {
        try {
            $this->recorder->record(
                signalType: self::SIGNAL_ACCESS_DENIED,
                severity: self::SEVERITY_WARNING,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                ipAddress: $ipAddress,
                userAgent: null,
                metadata: [
                    'resource' => $resource,
                    'action' => $action
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordAccessDenied', $e);
        }
    }

    /**
     * Used when secondary verification (2FA/OTP) fails.
     */
    public function recordStepUpFailed(int $adminId, string $mechanism, string $ipAddress): void
    {
        try {
            $this->recorder->record(
                signalType: self::SIGNAL_STEP_UP_FAILED,
                severity: self::SEVERITY_WARNING,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                ipAddress: $ipAddress,
                userAgent: null,
                metadata: [
                    'mechanism' => $mechanism
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordStepUpFailed', $e);
        }
    }

    /**
     * Used when a session is forcefully revoked or expires.
     */
    public function recordSessionTerminated(int $adminId, string $reason, string $ipAddress): void
    {
        try {
            $this->recorder->record(
                signalType: self::SIGNAL_SESSION_TERMINATED,
                severity: self::SEVERITY_INFO,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                ipAddress: $ipAddress,
                userAgent: null,
                metadata: [
                    'reason' => $reason
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordSessionTerminated', $e);
        }
    }

    private function logFailure(string $method, Throwable $e): void
    {
        $this->logger->error(
            sprintf('[SecuritySignalsService] %s failed: %s', $method, $e->getMessage()),
            ['exception' => $e]
        );
    }
}
