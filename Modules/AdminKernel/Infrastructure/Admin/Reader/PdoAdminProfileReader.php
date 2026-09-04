<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-21 14:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Admin\Reader;

use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminProfileReaderInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use PDO;
use RuntimeException;

final class PdoAdminProfileReader implements AdminProfileReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminIdentifierCryptoServiceInterface $cryptoService
    )
    {
    }

    public function getProfile(int $adminId): array
    {
        $sql = <<<SQL
SELECT
    a.id,
    a.display_name,
    a.status,
    a.created_at,

    (SELECT ae.email_ciphertext
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_ciphertext,

    (SELECT ae.email_iv
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_iv,

    (SELECT ae.email_tag
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_tag,

    (SELECT ae.email_key_id
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_key_id,

    (SELECT ae.verification_status
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_verification_status,

    (SELECT ae.verified_at
       FROM admin_emails ae
      WHERE ae.admin_id = a.id
      ORDER BY ae.id ASC
      LIMIT 1) AS email_verified_at

FROM admins a
WHERE a.id = :admin_id
LIMIT 1
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'admin_id' => $adminId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Admin not found');
        }

        /** @var array<string, mixed> $row */
        assert(array_key_exists('id', $row));
        assert(is_scalar($row['id']));
        assert(
            is_int($row['id'])
            || (is_string($row['id']) && ctype_digit($row['id']))
        );

        assert(is_string($row['status']));
        assert(is_string($row['created_at']));


        $maskedEmail = '—';

        if (! empty($row['email_ciphertext'])) {

            assert(
                is_string($row['email_ciphertext'])
                || is_resource($row['email_ciphertext'])
            );

            assert(
                is_string($row['email_iv'])
                || is_resource($row['email_iv'])
            );

            assert(
                is_string($row['email_tag'])
                || is_resource($row['email_tag'])
            );

            assert(is_string($row['email_key_id']));

            $ciphertext = $row['email_ciphertext'];
            $iv = $row['email_iv'];
            $tag = $row['email_tag'];

            if (is_resource($ciphertext)) {
                $ciphertext = stream_get_contents($ciphertext);
            }
            if (is_resource($iv)) {
                $iv = stream_get_contents($iv);
            }
            if (is_resource($tag)) {
                $tag = stream_get_contents($tag);
            }

            $payload = new EncryptedPayloadDTO(
                ciphertext: (string) $ciphertext,
                iv: (string) $iv,
                tag: (string) $tag,
                keyId: $row['email_key_id']
            );

            $decrypted = $this->cryptoService->decryptEmail($payload);

            if ($decrypted !== '') {
                $maskedEmail = $this->maskEmail($decrypted);
            }
        }

        return [
            'admin' => [
                'id'           => (int)$row['id'],
                'display_name' => is_string($row['display_name']) ? $row['display_name'] : null,
                'status' => (string) $row['status'], // ACTIVE | SUSPENDED | DISABLED
                'created_at' => (string) $row['created_at'],
            ],

            'email' => [
                'masked_address'      => $maskedEmail,
                'verification_status' => is_string($row['email_verification_status'])
                    ? $row['email_verification_status']
                    : null,

                'verified_at' => is_string($row['email_verified_at'])
                    ? $row['email_verified_at']
                    : null,
            ],
        ];
    }

    /**
     * Mask email for profile display only.
     * Plaintext email MUST NOT escape this reader.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return '****';
        }

        [$local, $domain] = $parts;

        if ($local === '') {
            return '****@' . $domain;
        }

        $firstChar = mb_substr($local, 0, 1);

        return $firstChar . '***@' . $domain;
    }
}
