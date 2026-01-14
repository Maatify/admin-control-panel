<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\DTO\Crypto\EncryptedPayloadDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\IdentifierNotFoundException;
use PDO;
use RuntimeException;

class AdminEmailRepository implements AdminEmailVerificationRepositoryInterface, AdminIdentifierLookupInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addEmail(int $adminId, string $blindIndex, EncryptedPayloadDTO $encryptedEmail): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO admin_emails (admin_id, email_blind_index, email_ciphertext, email_iv, email_tag, email_key_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $adminId,
            $blindIndex,
            $encryptedEmail->ciphertext,
            $encryptedEmail->iv,
            $encryptedEmail->tag,
            $encryptedEmail->keyId
        ]);
    }

    public function findByBlindIndex(string $blindIndex): ?int
    {
        $stmt = $this->pdo->prepare("SELECT admin_id FROM admin_emails WHERE email_blind_index = ?");
        $stmt->execute([$blindIndex]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int)$result : null;
    }

    public function getEncryptedEmail(int $adminId): ?EncryptedPayloadDTO
    {
        $stmt = $this->pdo->prepare("SELECT email_ciphertext, email_iv, email_tag, email_key_id FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        if (!is_array($result)) {
            throw new RuntimeException("PDO fetch returned unexpected type.");
        }

        /** @var array{email_ciphertext: mixed, email_iv: mixed, email_tag: mixed, email_key_id: mixed} $result */

        if (!array_key_exists('email_ciphertext', $result) ||
            !array_key_exists('email_iv', $result) ||
            !array_key_exists('email_tag', $result) ||
            !array_key_exists('email_key_id', $result)
        ) {
             throw new RuntimeException("Database result missing required columns.");
        }

        $ciphertext = $this->normalizeVarbinary($result['email_ciphertext']);
        $iv         = $this->normalizeVarbinary($result['email_iv']);
        $tag        = $this->normalizeVarbinary($result['email_tag']);
        $keyId      = $this->normalizeVarbinary($result['email_key_id']);

        if ($keyId === '') {
            throw new RuntimeException("Invalid key ID: cannot be empty.");
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
                throw new RuntimeException("Failed to read stream resource.");
            }
            return $content;
        }

        throw new RuntimeException("Invalid data type from DB: expected string or resource.");
    }

    public function getVerificationStatus(int $adminId): VerificationStatus
    {
        $stmt = $this->pdo->prepare("SELECT verification_status FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        if ($result === false) {
            throw new IdentifierNotFoundException("Admin email not found for ID: $adminId");
        }

        return VerificationStatus::from((string)$result);
    }

    public function markVerified(int $adminId, string $timestamp): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = ? WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::VERIFIED->value, $timestamp, $adminId]);
    }

    public function markFailed(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = NULL WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::FAILED->value, $adminId]);
    }

    public function markPending(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = NULL WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::PENDING->value, $adminId]);
    }
}
