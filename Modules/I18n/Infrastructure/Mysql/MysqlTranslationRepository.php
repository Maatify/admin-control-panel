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

use Maatify\I18n\DTO\TranslationUpsertResultDTO;
use PDO;
use PDOStatement;
use Maatify\I18n\Contract\TranslationRepositoryInterface;
use Maatify\I18n\DTO\TranslationCollectionDTO;
use Maatify\I18n\DTO\TranslationDTO;

final readonly class MysqlTranslationRepository implements TranslationRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function upsert(int $languageId, int $keyId, string $value): TranslationUpsertResultDTO
    {
        $sql = 'INSERT INTO i18n_translations (language_id, key_id, value)
            VALUES (:language_id, :key_id, :value)
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                value = VALUES(value),
                updated_at = CURRENT_TIMESTAMP';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return new TranslationUpsertResultDTO(0, false);
        }

        $stmt->execute([
            'language_id' => $languageId,
            'key_id' => $keyId,
            'value' => $value,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $created = ($stmt->rowCount() === 1);

        return new TranslationUpsertResultDTO($id, $created);
    }

    public function getById(int $id): ?TranslationDTO
    {
        $sql = 'SELECT id, key_id, language_id, value, created_at, updated_at
                FROM i18n_translations
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function getByLanguageAndKey(int $languageId, int $keyId): ?TranslationDTO
    {
        $sql = 'SELECT id, key_id, language_id, value, created_at, updated_at
                FROM i18n_translations
                WHERE language_id = :language_id AND key_id = :key_id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute([
            'language_id' => $languageId,
            'key_id' => $keyId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function listByLanguage(int $languageId): TranslationCollectionDTO
    {
        $sql = 'SELECT id, key_id, language_id, value, created_at, updated_at
                FROM i18n_translations
                WHERE language_id = :language_id
                ORDER BY key_id ASC';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return new TranslationCollectionDTO([]);
        }

        $stmt->execute(['language_id' => $languageId]);

        $items = [];

        while (true) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                break;
            }

            $dto = $this->mapRowToDTO($row);
            if ($dto !== null) {
                $items[] = $dto;
            }
        }

        return new TranslationCollectionDTO($items);
    }

    public function listByKey(int $keyId): TranslationCollectionDTO
    {
        $sql = 'SELECT id, key_id, language_id, value, created_at, updated_at
                FROM i18n_translations
                WHERE key_id = :key_id
                ORDER BY language_id ASC';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return new TranslationCollectionDTO([]);
        }

        $stmt->execute(['key_id' => $keyId]);

        $items = [];

        while (true) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                break;
            }

            $dto = $this->mapRowToDTO($row);
            if ($dto !== null) {
                $items[] = $dto;
            }
        }

        return new TranslationCollectionDTO($items);
    }

    public function deleteByLanguageAndKey(int $languageId, int $keyId): int
    {
        $sql = 'DELETE FROM i18n_translations
            WHERE language_id = :language_id AND key_id = :key_id';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return 0;
        }

        $stmt->execute([
            'language_id' => $languageId,
            'key_id' => $keyId,
        ]);

        return $stmt->rowCount();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDTO(array $row): ?TranslationDTO
    {
        $id = $row['id'] ?? null;
        $keyId = $row['key_id'] ?? null;
        $languageId = $row['language_id'] ?? null;
        $value = $row['value'] ?? null;
        $createdAt = $row['created_at'] ?? null;

        if (!is_numeric($id) || !is_numeric($keyId) || !is_numeric($languageId) || !is_string($value)) {
            return null;
        }

        $createdAtStr = is_string($createdAt) ? $createdAt : null;
        if ($createdAtStr === null || $createdAtStr === '') {
            return null;
        }

        $updatedAt = $row['updated_at'] ?? null;
        $updatedAtStr = is_string($updatedAt) ? $updatedAt : null;

        return new TranslationDTO(
            (int) $id,
            (int) $keyId,
            (int) $languageId,
            $value,
            $createdAtStr,
            $updatedAtStr
        );
    }

    public function existsByLanguageAndKey(int $languageId, int $keyId): bool
    {
        $sql = 'SELECT 1
            FROM i18n_translations
            WHERE language_id = :language_id AND key_id = :key_id
            LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return false;
        }

        $stmt->execute([
            'language_id' => $languageId,
            'key_id' => $keyId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<int> $keyIds
     */
    public function countByLanguageAndKeyIds(int $languageId, array $keyIds): int
    {
        if ($keyIds === []) {
            return 0;
        }

        // safe placeholders
        $placeholders = implode(',', array_fill(0, count($keyIds), '?'));

        $sql = "SELECT COUNT(*) 
            FROM i18n_translations
            WHERE language_id = ?
              AND key_id IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return 0;
        }

        $params = array_merge([$languageId], $keyIds);

        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

}
