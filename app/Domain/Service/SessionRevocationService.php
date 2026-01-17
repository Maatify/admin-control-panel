<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Context\RequestContext;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Exception\IdentifierNotFoundException;
use DomainException;
use DateTimeImmutable;
use PDO;

class SessionRevocationService
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $repository,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private PDO $pdo
    ) {
    }

    public function revoke(string $token, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            // Retrieve session info for audit context before revoking
            $session = $this->repository->findSession($token);
            $actorId = $session ? (int)$session['admin_id'] : 0;
            $sessionId = hash('sha256', $token);

            $this->repository->revokeSession($token);

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'session_revoked',
                'session',
                null,
                'MEDIUM',
                [
                    // Log the Session ID prefix (Safe), NOT the Token prefix
                    'session_id_prefix' => substr($sessionId, 0, 8) . '...',
                    'ip_address' => $context->ipAddress,
                    'reason' => 'explicit_revocation'
                ],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param string[] $hashes
     */
    public function revokeBulk(array $hashes, string $currentSessionHash, RequestContext $context): void
    {
        if (empty($hashes)) {
            return;
        }

        if (in_array($currentSessionHash, $hashes, true)) {
            throw new DomainException('Cannot revoke own session via bulk operation.');
        }

        $this->pdo->beginTransaction();
        try {
            // Fetch affected admins for audit
            $sessionAdminMap = $this->repository->findAdminsBySessionHashes($hashes);

            // Filter only sessions that actually exist (security/audit accuracy)
            $validHashes = array_keys($sessionAdminMap);
            $affectedAdminIds = array_unique(array_values($sessionAdminMap));

            if (empty($validHashes)) {
                 $this->pdo->commit();
                 return;
            }

            // Revoke
            $this->repository->revokeSessionsByHash($validHashes);

            // Audit
            // We need actor ID.
            $currentSession = $this->repository->findSessionByHash($currentSessionHash);
            $actorId = $currentSession ? (int)$currentSession['admin_id'] : 0;

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'sessions_bulk_revoked',
                'session',
                null,
                'MEDIUM',
                [
                    'count' => count($validHashes),
                    'affected_admin_ids' => array_values($affectedAdminIds),
                    'session_id_prefixes' => array_map(fn($h) => substr($h, 0, 8) . '...', $validHashes),
                    'ip_address' => $context->ipAddress,
                ],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeByHash(string $targetHash, string $currentSessionHash, RequestContext $context): int
    {
        if (hash_equals($targetHash, $currentSessionHash)) {
            throw new DomainException('Cannot revoke own session via global view.');
        }

        $this->pdo->beginTransaction();
        try {
            // Retrieve session info for audit context
            $session = $this->repository->findSessionByHash($targetHash);

            if ($session === null) {
                // If session doesn't exist, we can't revoke it.
                // We could throw exception or return. For idempotency, maybe return?
                // But if it's "blindly" invalid, we should probably tell the caller.
                throw new IdentifierNotFoundException('Session not found.');
            }

            $targetAdminId = (int)$session['admin_id'];

            // We need the Actor ID (the one performing the revocation).
            // But this service doesn't know who the actor is unless we pass it, or fetch it from current session.
            // Since we have currentSessionHash, we can fetch the actor.
            $currentSession = $this->repository->findSessionByHash($currentSessionHash);
            $actorId = $currentSession ? (int)$currentSession['admin_id'] : 0;

            $this->repository->revokeSessionByHash($targetHash);

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'session_revoked',
                'session',
                null, // Target ID is null for generic target? Or should we use session ID?
                // The DB schema for audit_logs has target_type and target_id.
                // target_id is BIGINT. Session ID is string.
                // So target_id must be null or something numeric.
                // We should put details in payload.
                // However, target_type 'session' implies we should log session ID somewhere.
                // Previous code passed `null` for target_id.
                'MEDIUM',
                [
                    'revoked_session_id_prefix' => substr($targetHash, 0, 8) . '...',
                    'target_admin_id' => $targetAdminId,
                    'ip_address' => $context->ipAddress,
                    'reason' => 'global_view_revocation'
                ],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
            return $targetAdminId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeAll(int $adminId, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->repository->revokeAllSessions($adminId);

            $this->auditWriter->write(new AuditEventDTO(
                $adminId,
                'all_sessions_revoked',
                'admin',
                $adminId,
                'HIGH',
                [
                    'ip_address' => $context->ipAddress,
                    'reason' => 'bulk_revocation'
                ],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
