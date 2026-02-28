<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 11:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Languages;

use Maatify\AdminKernel\Domain\I18n\Language\DTO\LanguageListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Language\LanguageLookupInterface;
use Maatify\LanguageCore\Enum\TextDirectionEnum;
use PDO;
use RuntimeException;

final readonly class PdoLanguageLookup implements LanguageLookupInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getById(int $id): ?LanguageListItemDTO
    {
        $sql = "
            SELECT
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                ls.direction,
                ls.icon,
                ls.sort_order
            FROM languages l
            LEFT JOIN language_settings ls ON ls.language_id = l.id
            WHERE l.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare language lookup query');
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array{
         *     id: int|string,
         *     name: string,
         *     code: string,
         *     is_active: int|string,
         *     fallback_language_id: int|string|null,
         *     direction: string|null,
         *     icon: string|null,
         *     sort_order: int|string|null,
         *     created_at: string,
         *     updated_at: string|null
         * } $row
         */
        return new LanguageListItemDTO(
            id: (int)$row['id'],
            name: (string)$row['name'],
            code: (string)$row['code'],
            isActive: ((int)$row['is_active']) === 1,
            fallbackLanguageId: $row['fallback_language_id'] !== null
                ? (int)$row['fallback_language_id']
                : null,
            direction: isset($row['direction'])
                ? TextDirectionEnum::from((string)$row['direction'])
                : TextDirectionEnum::LTR,
            icon: is_string($row['icon'] ?? null) ? $row['icon'] : null,
            sortOrder: isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
            createdAt: (string)$row['created_at'],
            updatedAt: is_string($row['updated_at'] ?? null)
                ? $row['updated_at']
                : null
        );
    }
}
