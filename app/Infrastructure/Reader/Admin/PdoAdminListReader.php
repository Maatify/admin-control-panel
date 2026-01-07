<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Admin;

use App\Domain\Contracts\AdminListReaderInterface;
use App\Domain\DTO\AdminConfigDTO;
use PDO;

class PdoAdminListReader implements AdminListReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {
    }

    /**
     * @return array<int, array{id: int, identifier: string}>
     */
    public function getAdmins(): array
    {
        // Fetch admins with their primary email
        $sql = "SELECT
                    a.id,
                    (
                        SELECT ae.email_encrypted
                        FROM admin_emails ae
                        WHERE ae.admin_id = a.id
                        ORDER BY ae.id ASC
                        LIMIT 1
                    ) as email_encrypted
                FROM admins a
                ORDER BY a.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $admins = [];
        if ($results !== false) {
            foreach ($results as $row) {
                /** @var array{id: int|string, email_encrypted: string|null} $row */
                $id = (int)$row['id'];
                $identifier = "Admin #$id";

                if (!empty($row['email_encrypted'])) {
                    $decrypted = $this->decryptEmail($row['email_encrypted']);
                    if ($decrypted !== null) {
                        $identifier = $decrypted;
                    }
                }

                $admins[] = [
                    'id' => $id,
                    'identifier' => $identifier
                ];
            }
        }

        return $admins;
    }

    private function decryptEmail(string $encryptedEmail): ?string
    {
        try {
            $data = base64_decode($encryptedEmail, true);
            if ($data === false) {
                return null;
            }

            $cipher = 'aes-256-gcm';
            $ivLen = openssl_cipher_iv_length($cipher);
            if ($ivLen === false) {
                return null;
            }

            if (strlen($data) < $ivLen + 16) {
                return null;
            }

            $iv = substr($data, 0, $ivLen);
            $tag = substr($data, $ivLen, 16);
            $ciphertext = substr($data, $ivLen + 16);

            $decrypted = openssl_decrypt(
                $ciphertext,
                $cipher,
                $this->config->emailEncryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $decrypted !== false ? $decrypted : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
