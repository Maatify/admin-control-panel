<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\DTO\AuditEventDTO;
use DateTimeImmutable;
use PDO;

class SessionRevocationService
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $repository,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private ClientInfoProviderInterface $clientInfoProvider,
        private PDO $pdo
    ) {
    }

    public function revoke(string $token): void
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
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'reason' => 'explicit_revocation'
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeAll(int $adminId): void
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
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'reason' => 'bulk_revocation'
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
