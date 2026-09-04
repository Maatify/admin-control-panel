<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use DomainException;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminSessionValidationRepositoryInterface;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\Exception\SessionRevocationFailedException;
use PDO;

class SessionRevocationService
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $repository,
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
            throw new SessionRevocationFailedException('Cannot revoke own session via bulk operation.');
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

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeByHash(string $targetHash, string $currentSessionHash, RequestContext $context): int
    {
        if (hash_equals($targetHash, $currentSessionHash)) {
            throw new SessionRevocationFailedException('Cannot revoke own session via global view.');
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

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    /**
     * Revoke all ACTIVE (non-expired) sessions for a given admin.
     *
     * Used when admin status changes to SUSPENDED / DISABLED.
     *
     * - Only valid sessions are revoked
     * - Expired sessions are ignored
     * - Authoritative audit is written
     * - MUST be called inside an active transaction
     */
    public function revokeAllActiveForAdmin(
        int $targetAdminId,
        int $actorAdminId,
        RequestContext $context,
        string $reason
    ): int
    {
        // IMPORTANT:
        // ❌ No beginTransaction() here
        // This service MAY be composed inside another transaction

        // 1️⃣ Fetch active sessions
        $activeSessionHashes = $this->repository->findActiveSessionHashesByAdmin($targetAdminId);

        if ($activeSessionHashes === []) {
            return 0;
        }

        // 2️⃣ Revoke
        $this->repository->revokeSessionsByHash($activeSessionHashes);

        // 3️⃣ Audit


        return count($activeSessionHashes);
    }

}
