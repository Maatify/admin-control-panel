<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\ClockInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\DTO\StepUpGrant;
use App\Domain\Enum\Scope;
use DateTimeImmutable;
use PDO;

class PdoStepUpGrantRepository implements StepUpGrantRepositoryInterface
{
    private PDO $pdo;
    private ClockInterface $clock;

    public function __construct(PDO $pdo, ClockInterface $clock)
    {
        $this->pdo = $pdo;
        $this->clock = $clock;
    }

    public function save(StepUpGrant $grant): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use, context_snapshot)
            VALUES (:admin_id, :session_id, :scope, :risk_context_hash, :issued_at, :expires_at, :single_use, :context_snapshot)
            ON DUPLICATE KEY UPDATE
                risk_context_hash = VALUES(risk_context_hash),
                issued_at = VALUES(issued_at),
                expires_at = VALUES(expires_at),
                single_use = VALUES(single_use),
                context_snapshot = VALUES(context_snapshot)
        ');

        $contextSnapshot = json_encode($grant->contextSnapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmt->execute([
            ':admin_id' => $grant->adminId,
            ':session_id' => $grant->sessionId,
            ':scope' => $grant->scope->value,
            ':risk_context_hash' => $grant->riskContextHash,
            ':issued_at' => $grant->issuedAt->format('Y-m-d H:i:s'),
            ':expires_at' => $grant->expiresAt->format('Y-m-d H:i:s'),
            ':single_use' => $grant->singleUse ? 1 : 0,
            ':context_snapshot' => $contextSnapshot,
        ]);
    }

    public function find(int $adminId, string $sessionId, Scope $scope): ?StepUpGrant
    {
        $stmt = $this->pdo->prepare('
            SELECT admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use, context_snapshot
            FROM step_up_grants
            WHERE admin_id = :admin_id AND session_id = :session_id AND scope = :scope
        ');

        $stmt->execute([
            ':admin_id' => $adminId,
            ':session_id' => $sessionId,
            ':scope' => $scope->value,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        // PHPStan hint
        /** @var array{admin_id: int|string, session_id: string, scope: string, risk_context_hash: string, issued_at: string, expires_at: string, single_use: int|string, context_snapshot: string|null} $result */

        $contextSnapshot = [];
        if (!empty($result['context_snapshot'])) {
             $decoded = json_decode((string)$result['context_snapshot'], true);
             if (is_array($decoded)) {
                 $contextSnapshot = $decoded;
             }
        }

        return new StepUpGrant(
            (int)$result['admin_id'],
            (string)$result['session_id'],
            Scope::from((string)$result['scope']),
            (string)$result['risk_context_hash'],
            new DateTimeImmutable((string)$result['issued_at'], $this->clock->getTimezone()),
            new DateTimeImmutable((string)$result['expires_at'], $this->clock->getTimezone()),
            (bool)$result['single_use'],
            (array)$contextSnapshot
        );
    }

    public function revoke(int $adminId, string $sessionId, Scope $scope): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM step_up_grants
            WHERE admin_id = :admin_id AND session_id = :session_id AND scope = :scope
        ');

        $stmt->execute([
            ':admin_id' => $adminId,
            ':session_id' => $sessionId,
            ':scope' => $scope->value,
        ]);
    }

    public function revokeAll(int $adminId): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM step_up_grants
            WHERE admin_id = :admin_id
        ');

        $stmt->execute([':admin_id' => $adminId]);
    }
}
