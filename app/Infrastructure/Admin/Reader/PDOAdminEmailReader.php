<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-24 13:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Admin\Reader;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\Admin\DTO\AdminEmailListItemDTO;
use App\Domain\Admin\Reader\AdminEmailReaderInterface;
use App\Domain\DTO\Crypto\EncryptedPayloadDTO;
use App\Domain\Enum\VerificationStatus;
use PDO;

readonly class PDOAdminEmailReader implements AdminEmailReaderInterface
{

    public function __construct(
        private PDO $pdo,
        private AdminIdentifierCryptoServiceInterface $cryptoService
    ){}

    /**
     * @return AdminEmailListItemDTO[]
     */
    public function listByAdminId(int $adminId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
    id,
    email_ciphertext,
    email_iv,
    email_tag,
    email_key_id,
    verification_status,
    verified_at
FROM admin_emails
WHERE admin_id = :admin_id
ORDER BY id ASC"
        );
        $stmt->execute([
            'admin_id' => $adminId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $items = [];

        /**
         * @var array<int, array{
         *   id: int|string,
         *   email_ciphertext: string,
         *   email_iv: string,
         *   email_tag: string,
         *   email_key_id: string,
         *   verification_status: string,
         *   verified_at: string|null
         * }> $rows
         */
        foreach ($rows as $row) {
            $payload = new EncryptedPayloadDTO(
                ciphertext: (string) $row['email_ciphertext'],
                iv: (string) $row['email_iv'],
                tag: (string) $row['email_tag'],
                keyId: $row['email_key_id']
            );

            $decrypted = $this->cryptoService->decryptEmail($payload);

            $items[] = new AdminEmailListItemDTO(
                emailId: (int) $row['id'],
                email: $decrypted,
                status: VerificationStatus::from((string) $row['verification_status']),
                verifiedAt: $row['verified_at'] ? (string) $row['verified_at'] : null
            );
        }

        return $items;
    }
}
