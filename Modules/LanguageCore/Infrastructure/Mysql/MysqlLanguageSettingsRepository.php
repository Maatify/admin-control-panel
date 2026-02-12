<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/language-core
 * @Project     maatify:language-core
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/language-core view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\LanguageCore\Infrastructure\Mysql;

use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Maatify\LanguageCore\DTO\LanguageSettingsDTO;
use Maatify\LanguageCore\Enum\TextDirectionEnum;
use PDO;
use PDOStatement;

final readonly class MysqlLanguageSettingsRepository implements LanguageSettingsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getByLanguageId(int $languageId): ?LanguageSettingsDTO
    {
        $sql = 'SELECT language_id, direction, icon, sort_order
                FROM language_settings
                WHERE language_id = :language_id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['language_id' => $languageId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function upsert(
        int $languageId,
        TextDirectionEnum $direction,
        ?string $icon,
    ): bool {
        $sql = 'INSERT INTO language_settings (language_id, direction, icon)
                VALUES (:language_id, :direction, :icon)
                ON DUPLICATE KEY UPDATE
                    direction = VALUES(direction),
                    icon = VALUES(icon)';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'language_id' => $languageId,
            'direction' => $direction->value,
            'icon' => $icon,
        ]);
    }

    public function updateDirection(int $languageId, TextDirectionEnum $direction): bool
    {
        $sql = 'UPDATE language_settings SET direction = :direction WHERE language_id = :language_id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'language_id' => $languageId,
            'direction' => $direction->value,
        ]);
    }

    public function updateIcon(int $languageId, ?string $icon): bool
    {
        $sql = 'UPDATE language_settings SET icon = :icon WHERE language_id = :language_id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'language_id' => $languageId,
            'icon' => $icon,
        ]);
    }

    public function updateSortOrder(int $languageId, int $sortOrder): bool
    {
        $sql = 'UPDATE language_settings SET sort_order = :sort_order WHERE language_id = :language_id';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) { return false; }

        return $stmt->execute([
            'language_id' => $languageId,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDTO(array $row): ?LanguageSettingsDTO
    {
        $languageId = $row['language_id'] ?? null;
        $direction = $row['direction'] ?? null;
        $sortOrder = $row['sort_order'] ?? null;

        if (!is_numeric($languageId) || !is_string($direction) || $direction === '' || !is_numeric($sortOrder)) {
            return null;
        }

        // Validate enum value early; invalid direction -> null
        try {
            $directionEnum = TextDirectionEnum::from($direction);
        } catch (\ValueError) {
            return null;
        }

        $icon = $row['icon'] ?? null;
        $iconStr = is_string($icon) ? $icon : null;

        return new LanguageSettingsDTO(
            (int) $languageId,
            $directionEnum,
            $iconStr,
            (int) $sortOrder
        );
    }

    public function repositionSortOrder(
        int $languageId,
        int $currentSort,
        int $targetSort
    ): void {
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            if ($targetSort < $currentSort) {
                // move up
                $stmt = $this->pdo->prepare(
                    '
                UPDATE language_settings
                SET sort_order = sort_order + 1
                WHERE sort_order >= :target
                  AND sort_order < :current
                '
                );

                if ($stmt instanceof \PDOStatement) {
                    $stmt->execute([
                        'target'  => $targetSort,
                        'current' => $currentSort,
                    ]);
                }
            } elseif ($targetSort > $currentSort) {
                // move down
                $stmt = $this->pdo->prepare(
                    '
                UPDATE language_settings
                SET sort_order = sort_order - 1
                WHERE sort_order > :current
                  AND sort_order <= :target
                '
                );

                if ($stmt instanceof \PDOStatement) {
                    $stmt->execute([
                        'current' => $currentSort,
                        'target'  => $targetSort,
                    ]);
                }
            }

            // place language at target position
            $stmt = $this->pdo->prepare(
                '
            UPDATE language_settings
            SET sort_order = :target
            WHERE language_id = :language_id
            '
            );

            if ($stmt instanceof \PDOStatement) {
                $stmt->execute([
                    'target'      => $targetSort,
                    'language_id' => $languageId,
                ]);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // fail-soft: do NOT rethrow
            return;
        }
    }

    public function getNextSortOrder(): int
    {
        $stmt = $this->pdo->query(
            'SELECT MAX(sort_order) FROM language_settings'
        );

        if ($stmt === false) {
            // FAIL-SOFT: default first position
            return 1;
        }

        $result = $stmt->fetchColumn();

        if ($result === false || $result === null) {
            return 1;
        }

        return ((int) $result) + 1;
    }

}
