<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\RememberMeRepositoryInterface;
use DateTimeImmutable;
use PDO;

class PdoRememberMeRepository implements RememberMeRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(
        int $adminId,
        string $selector,
        string $hashedValidator,
        string $userAgentHash,
        DateTimeImmutable $expiresAt
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO admin_remember_me_tokens (admin_id, selector, hashed_validator, user_agent_hash, expires_at)
             VALUES (:admin_id, :selector, :hashed_validator, :ua_hash, :expires_at)"
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':selector' => $selector,
            ':hashed_validator' => $hashedValidator,
            ':ua_hash' => $userAgentHash,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{id: int, admin_id: int, selector: string, hashed_validator: string, user_agent_hash: string, expires_at: string}|null
     */
    public function findBySelector(string $selector): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admin_remember_me_tokens WHERE selector = :selector LIMIT 1");
        $stmt->execute([':selector' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array{id: int, admin_id: int, selector: string, hashed_validator: string, user_agent_hash: string, expires_at: string} $row */
        return $row;
    }

    public function deleteBySelector(string $selector): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_remember_me_tokens WHERE selector = :selector");
        $stmt->execute([':selector' => $selector]);
    }

    public function deleteAllByAdminId(int $adminId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_remember_me_tokens WHERE admin_id = :admin_id");
        $stmt->execute([':admin_id' => $adminId]);
    }
}
