<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\VerificationCode;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationCodeStatus;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;
use PDO;

class PdoVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock
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
            'identity_type' => $code->identityType->value,
            'identity_id' => $code->identityId,
            'purpose' => $code->purpose->value,
            'code_hash' => $code->codeHash,
            'status' => $code->status->value,
            'attempts' => $code->attempts,
            'max_attempts' => $code->maxAttempts,
            'expires_at' => $code->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $code->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findActive(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): ?VerificationCode
    {
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
            'identity_type' => $identityType->value,
            'identity_id' => $identityId,
            'purpose' => $purpose->value,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !is_array($row)) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    public function findByCodeHash(string $codeHash): ?VerificationCode
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM verification_codes
            WHERE code_hash = :code_hash
            LIMIT 1
        ");

        $stmt->execute(['code_hash' => $codeHash]);

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

    public function expireAllFor(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): void
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
            'identity_type' => $identityType->value,
            'identity_id' => $identityId,
            'purpose' => $purpose->value,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDto(array $row): VerificationCode
    {
        /** @var int $id */
        $id = $row['id'];
        /** @var string $identityType */
        $identityType = $row['identity_type'];
        /** @var string $identityId */
        $identityId = $row['identity_id'];
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
            IdentityTypeEnum::from($identityType),
            $identityId,
            VerificationPurposeEnum::from($purpose),
            $codeHash,
            VerificationCodeStatus::from($statusStr),
            (int)$attempts,
            (int)$maxAttempts,
            new DateTimeImmutable($expiresAt, $this->clock->getTimezone()),
            new DateTimeImmutable($createdAt, $this->clock->getTimezone())
        );
    }
}
