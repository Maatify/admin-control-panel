<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopeDropdownItemDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopeDropdownResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDropdownReaderInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeDropdownReader implements I18nScopeDropdownReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getDropdownList(): I18nScopeDropdownResponseDTO
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                id,
                code,
                name
            FROM i18n_scopes
            WHERE is_active = 1
            ORDER BY sort_order ASC, code ASC
            "
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare scope dropdown query');
        }

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {
            $items[] = new I18nScopeDropdownItemDTO(
                (int) $row['id'],
                (string) $row['code'],
                (string) $row['name'],
            );
        }

        return new I18nScopeDropdownResponseDTO($items);
    }
}
