<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\DTO\Crypto\EncryptedPayloadDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\IdentifierNotFoundException;
use PDO;

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

        $ciphertext = $result['email_ciphertext'];
        $iv = $result['email_iv'];
        $tag = $result['email_tag'];

        if (is_resource($ciphertext)) {
            $ciphertext = stream_get_contents($ciphertext);
        }
        if (is_resource($iv)) {
            $iv = stream_get_contents($iv);
        }
        if (is_resource($tag)) {
            $tag = stream_get_contents($tag);
        }

        return new EncryptedPayloadDTO(
            ciphertext: (string)$ciphertext,
            iv: (string)$iv,
            tag: (string)$tag,
            keyId: (string)$result['email_key_id']
        );
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
