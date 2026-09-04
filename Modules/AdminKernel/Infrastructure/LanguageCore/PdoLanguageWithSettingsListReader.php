<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-24 22:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\LanguageCore;

use Maatify\AdminKernel\Domain\LanguageCore\DTO\LanguageWithSettingsItemDTO;
use Maatify\AdminKernel\Domain\LanguageCore\DTO\LanguageWithSettingsListResponseDTO;
use Maatify\AdminKernel\Domain\LanguageCore\LanguageWithSettingsListReaderInterface;
use PDO;

final readonly class PdoLanguageWithSettingsListReader implements LanguageWithSettingsListReaderInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function listAll(): LanguageWithSettingsListResponseDTO
    {
        $sql = "
            SELECT 
                l.id,
                l.name,
                l.code,
                l.fallback_language_id,
                s.direction,
                s.icon,
                s.sort_order
            FROM languages l
            INNER JOIN language_settings s 
                ON s.language_id = l.id
            WHERE l.is_active = 1
            ORDER BY s.sort_order ASC, l.name ASC
        ";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $items = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $direction = $row['direction'] ?? 'ltr';

            // Safety guard for literal type
            if ($direction !== 'ltr' && $direction !== 'rtl') {
                $direction = 'ltr';
            }

            $items[] = new LanguageWithSettingsItemDTO(
                id: (int) $row['id'],
                name: (string) $row['name'],
                code: (string) $row['code'],
                direction: $direction,
                icon: isset($row['icon']) ? (string) $row['icon'] : null,
                sort_order: (int) $row['sort_order'],
                fallback_language_id: isset($row['fallback_language_id'])
                    ? (int) $row['fallback_language_id']
                    : null,
            );
        }

        return new LanguageWithSettingsListResponseDTO($items);
    }
}
