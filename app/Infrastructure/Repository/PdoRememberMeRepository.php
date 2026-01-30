<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\DTO\RememberMeTokenDTO;
use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;
use PDO;

class PdoRememberMeRepository implements RememberMeRepositoryInterface
{
    private PDO $pdo;
    private ClockInterface $clock;

    public function __construct(PDO $pdo, ClockInterface $clock)
    {
        $this->pdo = $pdo;
        $this->clock = $clock;
    }

    public function save(RememberMeTokenDTO $token): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_remember_me_tokens (selector, hashed_validator, admin_id, expires_at, user_agent_hash)
            VALUES (:selector, :hashed_validator, :admin_id, :expires_at, :user_agent_hash)
        ");

        $stmt->execute([
            'selector' => $token->selector,
            'hashed_validator' => $token->hashedValidator,
            'admin_id' => $token->adminId,
            'expires_at' => $token->expiresAt->format('Y-m-d H:i:s'),
            'user_agent_hash' => $token->userAgentHash,
        ]);
    }

    public function findBySelector(string $selector): ?RememberMeTokenDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT selector, hashed_validator, admin_id, expires_at, user_agent_hash
            FROM admin_remember_me_tokens
            WHERE selector = :selector
        ");

        $stmt->execute(['selector' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !is_array($row)) {
            return null;
        }

        // Explicit type casting for PHPStan
        /** @var array{selector: string, hashed_validator: string, admin_id: int|string, expires_at: string, user_agent_hash: string} $row */

        return new RememberMeTokenDTO(
            $row['selector'],
            $row['hashed_validator'],
            (int)$row['admin_id'],
            new DateTimeImmutable($row['expires_at'], $this->clock->getTimezone()),
            $row['user_agent_hash']
        );
    }

    public function deleteBySelector(string $selector): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_remember_me_tokens WHERE selector = :selector");
        $stmt->execute(['selector' => $selector]);
    }

    public function deleteByAdminId(int $adminId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_remember_me_tokens WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $adminId]);
    }
}
