<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 16:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Reader;

use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopesListItemDTO;

interface I18nScopeDetailsRepositoryInterface
{
    /**
     * Scope overview (identity + metadata only)
     *
     * MUST NOT include:
     * - domain assignments
     * - relations
     * - governance rules
     */
    public function getScopeDetailsById(int $scopeId): I18nScopesListItemDTO;
}
