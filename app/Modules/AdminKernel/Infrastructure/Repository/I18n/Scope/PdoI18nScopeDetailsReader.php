<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 16:47
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Scope;

use Maatify\AdminKernel\Domain\DTO\I18nScopesList\I18nScopesListItemDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use PDO;

final readonly class PdoI18nScopeDetailsReader implements I18nScopeDetailsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}
    public function getScopeDetailsById(int $scopeId): I18nScopesListItemDTO
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT
                id,
                code,
                name,
                description,
                is_active,
                sort_order
            FROM i18n_scopes
            WHERE id = :id
            '
        );

        $stmt->execute(['id' => $scopeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new EntityNotFoundException('scope', $scopeId);
        }

        /**
         * @var  array{
         *   id:int,
         *   code:string,
         *   name:string,
         *   description:string|null,
         *   is_active:int,
         *   sort_order:int,
         * }$row
         */

        return new I18nScopesListItemDTO(
            id: (int) $row['id'],
            code: $row['code'],
            name: $row['name'],
            description: is_string($row['description'] ?? null) ? $row['description'] : '',
            is_active: (int) $row['is_active'],
            sort_order: (int) $row['sort_order'],
        );
    }
}
