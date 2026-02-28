<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/language-core
 * @Project     maatify:language-core
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/language-core view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\LanguageCore\Infrastructure\Mysql;

use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\DTO\LanguageCollectionDTO;
use Maatify\LanguageCore\DTO\LanguageDTO;
use PDO;
use PDOStatement;

final readonly class MysqlLanguageRepository implements LanguageRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function create(
        string $name,
        string $code,
        bool $isActive,
        ?int $fallbackLanguageId
    ): int {
        $sql = 'INSERT INTO languages (name, code, is_active, fallback_language_id)
                VALUES (:name, :code, :is_active, :fallback_language_id)';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return 0;
        }

        $stmt->execute([
            'name' => $name,
            'code' => $code,
            'is_active' => $isActive ? 1 : 0,
            'fallback_language_id' => $fallbackLanguageId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getById(int $id): ?LanguageDTO
    {
        $sql = 'SELECT id, name, code, is_active, fallback_language_id, created_at, updated_at
                FROM languages WHERE id = :id LIMIT 1';

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

    public function getByCode(string $code): ?LanguageDTO
    {
        $sql = 'SELECT id, name, code, is_active, fallback_language_id, created_at, updated_at
                FROM languages WHERE code = :code LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['code' => $code]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function listAll(): LanguageCollectionDTO
    {
        $sql = 'SELECT id, name, code, is_active, fallback_language_id, created_at, updated_at
                FROM languages ORDER BY id ASC';

        $stmt = $this->pdo->query($sql);
        if (!$stmt instanceof PDOStatement) {
            return new LanguageCollectionDTO([]);
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

        return new LanguageCollectionDTO($items);
    }

    public function listActiveForSelect(): LanguageCollectionDTO
    {
        $sql = '
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at
            FROM languages l
            INNER JOIN language_settings s
                ON s.language_id = l.id
            WHERE l.is_active = 1
            ORDER BY s.sort_order ASC, l.id ASC
        ';

        $stmt = $this->pdo->query($sql);
        if (!$stmt instanceof PDOStatement) {
            return new LanguageCollectionDTO([]);
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

        return new LanguageCollectionDTO($items);
    }

    public function setActive(int $id, bool $isActive): bool
    {
        $sql = 'UPDATE languages SET is_active = :is_active WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'id' => $id,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function setFallbackLanguage(int $id, ?int $fallbackLanguageId): bool
    {
        $sql = 'UPDATE languages SET fallback_language_id = :fallback_language_id WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'id' => $id,
            'fallback_language_id' => $fallbackLanguageId,
        ]);
    }

    public function clearFallbackLanguage(int $languageId): bool
    {
        $stmt = $this->pdo->prepare(
            '
        UPDATE languages
        SET fallback_language_id = NULL
        WHERE id = :language_id
        '
        );

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'language_id' => $languageId,
        ]);
    }

    public function updateName(int $id, string $name): bool
    {
        $sql = 'UPDATE languages SET name = :name WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'id' => $id,
            'name' => $name,
        ]);
    }

    public function updateCode(int $id, string $code): bool
    {
        $sql = 'UPDATE languages SET code = :code WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'id' => $id,
            'code' => $code,
        ]);
    }

    /**
     * Repository rule:
     * - If required columns are missing/invalid -> return null.
     *
     * @param array<string, mixed> $row
     */
    private function mapRowToDTO(array $row): ?LanguageDTO
    {
        $id = $row['id'] ?? null;
        $name = $row['name'] ?? null;
        $code = $row['code'] ?? null;
        $isActive = $row['is_active'] ?? null;
        $createdAt = $row['created_at'] ?? null;

        if (!is_numeric($id) || !is_string($name) || $name === '' || !is_string($code) || $code === '' || $createdAt === null) {
            return null;
        }

        $createdAtStr = is_string($createdAt) ? $createdAt : null;
        if ($createdAtStr === null || $createdAtStr === '') {
            return null;
        }

        $updatedAt = $row['updated_at'] ?? null;
        $updatedAtStr = is_string($updatedAt) ? $updatedAt : null;

        $fallback = $row['fallback_language_id'] ?? null;
        $fallbackId = null;
        if ($fallback !== null) {
            if (!is_numeric($fallback)) {
                return null;
            }
            $fallbackId = (int) $fallback;
        }

        $isActiveBool = false;
        if (is_numeric($isActive)) {
            $isActiveBool = ((int) $isActive) === 1;
        } elseif (is_bool($isActive)) {
            $isActiveBool = $isActive;
        } else {
            return null;
        }

        return new LanguageDTO(
            (int) $id,
            $name,
            $code,
            $isActiveBool,
            $fallbackId,
            $createdAtStr,
            $updatedAtStr
        );
    }

}
