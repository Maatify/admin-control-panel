<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\DTO\TranslationKeyCollectionDTO;
use Maatify\I18n\DTO\TranslationKeyDTO;

final readonly class MysqlTranslationKeyRepository implements TranslationKeyRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function create(string $key, ?string $description): int
    {
        $sql = 'INSERT INTO i18n_keys (translation_key, description)
                VALUES (:translation_key, :description)';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return 0;
        }

        $stmt->execute([
            'translation_key' => $key,
            'description' => $description,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getById(int $id): ?TranslationKeyDTO
    {
        $sql = 'SELECT id, translation_key, description, created_at
                FROM i18n_keys
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

    public function getByKey(string $key): ?TranslationKeyDTO
    {
        $sql = 'SELECT id, translation_key, description, created_at
                FROM i18n_keys
                WHERE translation_key = :translation_key
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['translation_key' => $key]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function listAll(): TranslationKeyCollectionDTO
    {
        $sql = 'SELECT id, translation_key, description, created_at
                FROM i18n_keys
                ORDER BY id ASC';

        $stmt = $this->pdo->query($sql);
        if (!$stmt instanceof PDOStatement) {
            return new TranslationKeyCollectionDTO([]);
        }

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

        return new TranslationKeyCollectionDTO($items);
    }

    public function updateDescription(int $id, ?string $description): void
    {
        $sql = 'UPDATE i18n_keys SET description = :description WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'id' => $id,
            'description' => $description,
        ]);
    }

    public function renameKey(int $id, string $newKey): void
    {
        $sql = 'UPDATE i18n_keys SET translation_key = :translation_key WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'id' => $id,
            'translation_key' => $newKey,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDTO(array $row): ?TranslationKeyDTO
    {
        $id = $row['id'] ?? null;
        $key = $row['translation_key'] ?? null;
        $createdAt = $row['created_at'] ?? null;

        if (!is_numeric($id) || !is_string($key) || $key === '') {
            return null;
        }

        $createdAtStr = is_string($createdAt) ? $createdAt : null;
        if ($createdAtStr === null || $createdAtStr === '') {
            return null;
        }

        $desc = $row['description'] ?? null;
        $descStr = is_string($desc) ? $desc : null;

        return new TranslationKeyDTO(
            (int) $id,
            $key,
            $descStr,
            $createdAtStr
        );
    }
}
