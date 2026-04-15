<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Infrastructure\Mysql;

use Maatify\LanguageCore\Contract\LanguageContextQueryInterface;
use Maatify\LanguageCore\DTO\LanguageCollectionDTO;
use Maatify\LanguageCore\DTO\LanguageDTO;
use PDO;
use PDOStatement;

final readonly class MysqlLanguageContextQuery implements LanguageContextQueryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getByIdWithContext(int $id): ?LanguageDTO
    {
        $sql = '
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                s.icon,
                s.direction
            FROM languages l
            LEFT JOIN language_settings s
                ON s.language_id = l.id
            WHERE l.id = :id
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute([
            'id' => $id,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function getByCodeWithContext(string $code): ?LanguageDTO
    {
        $sql = '
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                s.icon,
                s.direction
            FROM languages l
            LEFT JOIN language_settings s
                ON s.language_id = l.id
            WHERE l.code = :code
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute([
            'code' => $code,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function listAllWithContext(): LanguageCollectionDTO
    {
        $sql = '
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                s.icon,
                s.direction
            FROM languages l
            LEFT JOIN language_settings s
                ON s.language_id = l.id
            ORDER BY
                CASE WHEN s.sort_order IS NULL THEN 1 ELSE 0 END ASC,
                s.sort_order ASC,
                l.id ASC
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

    public function listActiveWithContext(): LanguageCollectionDTO
    {
        $sql = '
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                s.icon,
                s.direction
            FROM languages l
            LEFT JOIN language_settings s
                ON s.language_id = l.id
            WHERE l.is_active = 1
            ORDER BY
                CASE WHEN s.sort_order IS NULL THEN 1 ELSE 0 END ASC,
                s.sort_order ASC,
                l.id ASC
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

        if (
            !is_numeric($id) ||
            !is_string($name) || $name === '' ||
            !is_string($code) || $code === '' ||
            $createdAt === null
        ) {
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

        $icon = $row['icon'] ?? null;
        $iconStr = is_string($icon) && $icon !== '' ? $icon : null;

        $direction = $row['direction'] ?? null;
        $directionStr = is_string($direction) && $direction !== '' ? $direction : null;

        return new LanguageDTO(
            (int) $id,
            $name,
            $code,
            $isActiveBool,
            $fallbackId,
            $createdAtStr,
            $updatedAtStr,
            $iconStr,
            $directionStr
        );
    }
}
