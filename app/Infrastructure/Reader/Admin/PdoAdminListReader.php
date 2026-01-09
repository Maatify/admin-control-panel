<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Admin;

use App\Domain\Contracts\AdminListReaderInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AdminList\AdminListItemDTO;
use App\Domain\DTO\AdminList\AdminListQueryDTO;
use App\Domain\DTO\AdminList\AdminListResponseDTO;
use App\Domain\DTO\Common\PaginationDTO;
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

    public function listAdmins(AdminListQueryDTO $query): AdminListResponseDTO
    {
        $params = [];
        $whereClauses = ["1=1"];

        if ($query->adminId !== null) {
            $whereClauses[] = "a.id = :admin_id";
            $params['admin_id'] = $query->adminId;
        }

        if ($query->email !== null) {
            $normalizedEmail = strtolower(trim($query->email));
            $blindIndex = hash_hmac('sha256', $normalizedEmail, $this->config->emailBlindIndexKey);
            $whereClauses[] = "ae.email_blind_index = :blind_index";
            $params['blind_index'] = $blindIndex;
        }

        $whereSql = implode(' AND ', $whereClauses);

        // Count Total
        $countSql = "
            SELECT COUNT(DISTINCT a.id)
            FROM admins a
            LEFT JOIN admin_emails ae ON a.id = ae.admin_id
            WHERE {$whereSql}
        ";

        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch Data
        $sql = "
            SELECT
                a.id,
                a.created_at,
                (SELECT email_encrypted FROM admin_emails ae2 WHERE ae2.admin_id = a.id ORDER BY ae2.id ASC LIMIT 1) as email_encrypted
            FROM admins a
            LEFT JOIN admin_emails ae ON a.id = ae.admin_id
            WHERE {$whereSql}
            GROUP BY a.id
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $query->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', ($query->page - 1) * $query->perPage, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dtos = [];
        foreach ($rows as $row) {
            /** @var array{id: int|string, created_at: string, email_encrypted: ?string} $row */

            $email = 'N/A';
            if (!empty($row['email_encrypted'])) {
                $decrypted = $this->decryptEmail($row['email_encrypted']);
                if ($decrypted !== null) {
                    $email = $decrypted;
                }
            }

            $dtos[] = new AdminListItemDTO(
                id: (int)$row['id'],
                email: $email,
                createdAt: $row['created_at']
            );
        }

        $totalPages = $total > 0 ? (int)ceil($total / $query->perPage) : 1;

        return new AdminListResponseDTO(
            data: $dtos,
            pagination: new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $totalPages
            )
        );
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
