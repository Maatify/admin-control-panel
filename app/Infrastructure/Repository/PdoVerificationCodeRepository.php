<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\DTO\VerificationCode;
use App\Domain\Enum\VerificationCodeStatus;
use DateTimeImmutable;
use PDO;

class PdoVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function store(VerificationCode $code): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO verification_codes (
                identity_type, identity_id, purpose, code_hash,
                status, attempts, max_attempts, expires_at, created_at
            ) VALUES (
                :identity_type, :identity_id, :purpose, :code_hash,
                :status, :attempts, :max_attempts, :expires_at, :created_at
            )
        ");

        $stmt->execute([
            'identity_type' => $code->subjectType,
            'identity_id' => $code->subjectIdentifier,
            'purpose' => $code->purpose,
            'code_hash' => $code->codeHash,
            'status' => $code->status->value,
            'attempts' => $code->attempts,
            'max_attempts' => $code->maxAttempts,
            'expires_at' => $code->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $code->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findActive(string $subjectType, string $subjectIdentifier, string $purpose): ?VerificationCode
    {
        // Status must be active AND not expired.
        // Although the query could filter expiry, the prompt implies "Find active" and validator checks expiry?
        // But the schema rules say "Expired / used codes must never validate".
        // It's safer to filter by status='active' AND expires_at > NOW() in the query to avoid race conditions or returning stale data.
        // However, the Validator usually wants to know WHY it failed (expired vs invalid).
        // If I filter it out here, `validate` will think "Code not found".
        // If I return it, `validate` can say "Expired".
        // The prompt: "Find active code" (in Validator section).
        // I will return the record if status is 'active', regardless of expiry, so the Validator can check expiry and return specific error if needed?
        // Actually, generic failure is required: "No distinction between expired, invalid, wrong".
        // So finding only valid active codes is fine.

        $stmt = $this->pdo->prepare("
            SELECT * FROM verification_codes
            WHERE identity_type = :identity_type
            AND identity_id = :identity_id
            AND purpose = :purpose
            AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([
            'identity_type' => $subjectType,
            'identity_id' => $subjectIdentifier,
            'purpose' => $purpose,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !is_array($row)) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    public function incrementAttempts(int $codeId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE verification_codes
            SET attempts = attempts + 1
            WHERE id = :id
        ");
        $stmt->execute(['id' => $codeId]);
    }

    public function markUsed(int $codeId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE verification_codes
            SET status = 'used'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $codeId]);
    }

    public function expire(int $codeId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE verification_codes
            SET status = 'expired'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $codeId]);
    }

    public function expireAllFor(string $subjectType, string $subjectIdentifier, string $purpose): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE verification_codes
            SET status = 'expired'
            WHERE identity_type = :identity_type
            AND identity_id = :identity_id
            AND purpose = :purpose
            AND status = 'active'
        ");
        $stmt->execute([
            'identity_type' => $subjectType,
            'identity_id' => $subjectIdentifier,
            'purpose' => $purpose,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDto(array $row): VerificationCode
    {
        /** @var int $id */
        $id = $row['id'];
        /** @var string $subjectType */
        $subjectType = $row['identity_type'];
        /** @var string $subjectIdentifier */
        $subjectIdentifier = $row['identity_id'];
        /** @var string $purpose */
        $purpose = $row['purpose'];
        /** @var string $codeHash */
        $codeHash = $row['code_hash'];
        /** @var string $statusStr */
        $statusStr = $row['status'];
        /** @var int|string $attempts */
        $attempts = $row['attempts'];
        /** @var int|string $maxAttempts */
        $maxAttempts = $row['max_attempts'];
        /** @var string $expiresAt */
        $expiresAt = $row['expires_at'];
        /** @var string $createdAt */
        $createdAt = $row['created_at'];

        return new VerificationCode(
            (int)$id,
            $subjectType,
            $subjectIdentifier,
            $purpose,
            $codeHash,
            VerificationCodeStatus::from($statusStr),
            (int)$attempts,
            (int)$maxAttempts,
            new DateTimeImmutable($expiresAt),
            new DateTimeImmutable($createdAt)
        );
    }
}
