<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 14:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainsDropdownResponseDTO;

interface I18nScopeDomainsListReaderInterface
{
    /**
     * @return I18nScopeDomainsDropdownResponseDTO
     */
    public function listByScopeId(int $scopeId): I18nScopeDomainsDropdownResponseDTO;
}
