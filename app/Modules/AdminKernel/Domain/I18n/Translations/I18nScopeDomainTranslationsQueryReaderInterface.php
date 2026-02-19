<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 18:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations;

use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainTranslationsListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface I18nScopeDomainTranslationsQueryReaderInterface
{
    public function queryScopeDomainTranslations(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainTranslationsListResponseDTO;
}
