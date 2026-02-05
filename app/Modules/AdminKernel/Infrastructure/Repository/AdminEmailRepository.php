<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminEmailVerificationRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminIdentifierLookupInterface;
use Maatify\AdminKernel\Domain\DTO\AdminEmailIdentifierDTO;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use Maatify\AdminKernel\Domain\Enum\VerificationStatus;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use PDO;
use RuntimeException;

class AdminEmailRepository implements AdminEmailVerificationRepositoryInterface, AdminIdentifierLookupInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addEmail(int $adminId, string $blindIndex, EncryptedPayloadDTO $encryptedEmail): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO admin_emails 
             (admin_id, email_blind_index, email_ciphertext, email_iv, email_tag, email_key_id) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $adminId,
            $blindIndex,
            $encryptedEmail->ciphertext,
            $encryptedEmail->iv,
            $encryptedEmail->tag,
            $encryptedEmail->keyId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByBlindIndex(string $blindIndex): ?AdminEmailIdentifierDTO
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT
                id          AS email_id,
                admin_id    AS admin_id,
                verification_status
            FROM admin_emails
            WHERE email_blind_index = :blind_index
            LIMIT 1
            SQL
        );

        $stmt->execute([
            'blind_index' => $blindIndex,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array{email_id:string|int, admin_id:string|int, verification_status:string} $row */
        return new AdminEmailIdentifierDTO(
            (int) $row['email_id'],
            (int) $row['admin_id'],
            VerificationStatus::from($row['verification_status'])
        );
    }

    public function getEncryptedEmail(int $adminId): ?EncryptedPayloadDTO
    {
        $stmt = $this->pdo->prepare(
            "SELECT email_ciphertext, email_iv, email_tag, email_key_id 
             FROM admin_emails 
             WHERE admin_id = ?"
        );
        $stmt->execute([$adminId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        /**
         * @var array{
         *   email_ciphertext: mixed,
         *   email_iv: mixed,
         *   email_tag: mixed,
         *   email_key_id: mixed
         * } $result
         */

        $ciphertext = $this->normalizeVarbinary($result['email_ciphertext']);
        $iv         = $this->normalizeVarbinary($result['email_iv']);
        $tag        = $this->normalizeVarbinary($result['email_tag']);
        $keyId      = $this->normalizeVarbinary($result['email_key_id']);

        if ($keyId === '') {
            throw new RuntimeException('Invalid key ID: cannot be empty.');
        }

        return new EncryptedPayloadDTO(
            ciphertext: $ciphertext,
            iv: $iv,
            tag: $tag,
            keyId: $keyId
        );
    }

    private function normalizeVarbinary(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_resource($value)) {
            $content = stream_get_contents($value);
            if ($content === false) {
                throw new RuntimeException('Failed to read stream resource.');
            }
            return $content;
        }

        throw new RuntimeException('Invalid data type from DB: expected string or resource.');
    }

    public function getEmailIdentity(int $emailId): AdminEmailIdentifierDTO
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
        SELECT
            id AS email_id,
            admin_id,
            verification_status
        FROM admin_emails
        WHERE id = :email_id
        LIMIT 1
        SQL
        );

        $stmt->execute([
            'email_id' => $emailId,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new IdentifierNotFoundException(
                "Admin email not found for ID: {$emailId}"
            );
        }

        /**
         * @var array{
         *   email_id: int|string,
         *   admin_id: int|string,
         *   verification_status: string
         * } $row
         */
        return new AdminEmailIdentifierDTO(
            (int) $row['email_id'],
            (int) $row['admin_id'],
            VerificationStatus::from($row['verification_status'])
        );
    }


    public function markVerified(int $emailId, string $timestamp): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_emails 
             SET verification_status = ?, verified_at = ? 
             WHERE id = ?"
        );

        $stmt->execute([
            VerificationStatus::VERIFIED->value,
            $timestamp,
            $emailId,
        ]);
    }

    public function markFailed(int $emailId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_emails 
             SET verification_status = ?, verified_at = NULL 
             WHERE id = ?"
        );

        $stmt->execute([
            VerificationStatus::FAILED->value,
            $emailId,
        ]);
    }

    public function markPending(int $emailId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_emails 
             SET verification_status = ?, verified_at = NULL 
             WHERE id = ?"
        );

        $stmt->execute([
            VerificationStatus::PENDING->value,
            $emailId,
        ]);
    }

    public function markReplaced(int $emailId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_emails 
             SET verification_status = ?, verified_at = NULL 
             WHERE id = ?"
        );

        $stmt->execute([
            VerificationStatus::REPLACED->value,
            $emailId,
        ]);
    }
}
