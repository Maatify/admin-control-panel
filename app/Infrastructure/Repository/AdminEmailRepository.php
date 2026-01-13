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
        $json = json_encode([
            'ciphertext' => $encryptedEmail->ciphertext,
            'iv' => $encryptedEmail->iv,
            'tag' => $encryptedEmail->tag,
            'keyId' => $encryptedEmail->keyId,
        ], JSON_THROW_ON_ERROR);

        $stmt = $this->pdo->prepare("INSERT INTO admin_emails (admin_id, email_blind_index, email_encrypted) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $blindIndex, $json]);
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
        $stmt = $this->pdo->prepare("SELECT email_encrypted FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        if ($result === false) {
            return null;
        }

        try {
            $data = json_decode((string)$result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data) || !isset($data['ciphertext'], $data['iv'], $data['tag'], $data['keyId'])) {
            return null;
        }

        return new EncryptedPayloadDTO(
            ciphertext: $data['ciphertext'],
            iv: $data['iv'],
            tag: $data['tag'],
            keyId: $data['keyId']
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
