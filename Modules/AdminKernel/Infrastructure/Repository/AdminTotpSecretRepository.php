<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-17 23:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use PDO;
use PDOException;
use RuntimeException;

final class AdminTotpSecretRepository implements AdminTotpSecretRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function save(int $adminId, EncryptedPayloadDTO $encryptedSeed): void
    {
        try {
            $stmt = $this->pdo->prepare(
                <<<SQL
                INSERT INTO admin_totp_secrets (
                    admin_id,
                    seed_ciphertext,
                    seed_iv,
                    seed_tag,
                    seed_key_id
                ) VALUES (
                    :admin_id,
                    :ciphertext,
                    :iv,
                    :tag,
                    :key_id
                )
                ON DUPLICATE KEY UPDATE
                    seed_ciphertext = VALUES(seed_ciphertext),
                    seed_iv         = VALUES(seed_iv),
                    seed_tag        = VALUES(seed_tag),
                    seed_key_id     = VALUES(seed_key_id),
                    rotated_at      = CURRENT_TIMESTAMP
                SQL
            );

            $stmt->execute([
                'admin_id'   => $adminId,
                'ciphertext' => $encryptedSeed->ciphertext,
                'iv'         => $encryptedSeed->iv,
                'tag'        => $encryptedSeed->tag,
                'key_id'     => $encryptedSeed->keyId,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to persist encrypted TOTP secret',
                previous: $e
            );
        }
    }

    public function get(int $adminId): ?EncryptedPayloadDTO
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT
                seed_ciphertext,
                seed_iv,
                seed_tag,
                seed_key_id
            FROM admin_totp_secrets
            WHERE admin_id = :admin_id
            LIMIT 1
            SQL
        );

        $stmt->execute(['admin_id' => $adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /**
         * @var array{
         *   seed_ciphertext: string,
         *   seed_iv: string,
         *   seed_tag: string,
         *   seed_key_id: string
         * } $row
         */
        return new EncryptedPayloadDTO(
            ciphertext: $row['seed_ciphertext'],
            iv        : $row['seed_iv'],
            tag       : $row['seed_tag'],
            keyId     : $row['seed_key_id']
        );

    }

    public function delete(int $adminId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM admin_totp_secrets WHERE admin_id = :admin_id'
        );

        $stmt->execute(['admin_id' => $adminId]);
    }
}
