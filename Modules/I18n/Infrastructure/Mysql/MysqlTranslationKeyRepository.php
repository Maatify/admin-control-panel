<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\DTO\TranslationKeyDTO;
use Maatify\I18n\DTO\TranslationKeyCollectionDTO;

final readonly class MysqlTranslationKeyRepository implements TranslationKeyRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(
        string $scope,
        string $domain,
        string $key,
        ?string $description
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO i18n_keys (scope, domain, key_part, description)
             VALUES (:scope, :domain, :key, :description)'
        );

        if (!$stmt instanceof PDOStatement) {
            return 0;
        }

        $stmt->execute([
            'scope' => $scope,
            'domain' => $domain,
            'key' => $key,
            'description' => $description,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getById(int $id): ?TranslationKeyDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, scope, domain, key_part, description, created_at
             FROM i18n_keys
             WHERE id = :id
             LIMIT 1'
        );

        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->map($row) : null;
    }

    public function getByStructuredKey(
        string $scope,
        string $domain,
        string $key
    ): ?TranslationKeyDTO {
        $stmt = $this->pdo->prepare(
            'SELECT id, scope, domain, key_part, description, created_at
             FROM i18n_keys
             WHERE scope = :scope
               AND domain = :domain
               AND key_part = :key
             LIMIT 1'
        );

        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute([
            'scope' => $scope,
            'domain' => $domain,
            'key' => $key,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->map($row) : null;
    }

    public function listAll(): TranslationKeyCollectionDTO
    {
        $stmt = $this->pdo->query(
            'SELECT id, scope, domain, key_part, description, created_at
             FROM i18n_keys
             ORDER BY id ASC'
        );

        if (!$stmt instanceof PDOStatement) {
            return new TranslationKeyCollectionDTO([]);
        }

        $items = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row)) {
                $items[] = $this->map($row);
            }
        }

        return new TranslationKeyCollectionDTO($items);
    }

    public function updateDescription(int $id, ?string $description): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE i18n_keys SET description = :description WHERE id = :id'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'id' => $id,
            'description' => $description,
        ]);
    }

    public function rename(
        int $id,
        string $scope,
        string $domain,
        string $key
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE i18n_keys
             SET scope = :scope,
                 domain = :domain,
                 key_part = :key
             WHERE id = :id'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'id' => $id,
            'scope' => $scope,
            'domain' => $domain,
            'key' => $key,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function map(array $row): TranslationKeyDTO
    {
        $idRaw = $row['id'] ?? null;
        $scopeRaw = $row['scope'] ?? null;
        $domainRaw = $row['domain'] ?? null;
        $keyPartRaw = $row['key_part'] ?? null;
        $createdAtRaw = $row['created_at'] ?? null;

        return new TranslationKeyDTO(
            is_numeric($idRaw) ? (int) $idRaw : 0,
            is_string($scopeRaw) ? $scopeRaw : '',
            is_string($domainRaw) ? $domainRaw : '',
            is_string($keyPartRaw) ? $keyPartRaw : '',
            is_string($row['description'] ?? null) ? $row['description'] : null,
            is_string($createdAtRaw) ? $createdAtRaw : ''
        );
    }

    public function listByScopeAndDomain(
        string $scope,
        string $domain
    ): TranslationKeyCollectionDTO {
        $sql = '
        SELECT
            id,
            scope,
            domain,
            key_part,
            description,
            created_at
        FROM i18n_keys
        WHERE scope = :scope
          AND domain = :domain
        ORDER BY key_part ASC
    ';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof \PDOStatement) {
            return new TranslationKeyCollectionDTO([]);
        }

        $stmt->execute([
            'scope' => $scope,
            'domain' => $domain,
        ]);

        $items = [];

        while (true) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                break;
            }

            $items[] = $this->map($row);
        }

        return new TranslationKeyCollectionDTO($items);
    }

}
