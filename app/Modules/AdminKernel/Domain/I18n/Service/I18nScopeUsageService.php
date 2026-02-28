<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 19:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Service;

use Maatify\AdminKernel\Domain\I18n\Keys\I18nKeyInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;

/**
 * Determines whether a domain code is actively used
 * either by scope bindings or by existing translation keys.
 */
final readonly class I18nScopeUsageService
{
    public function __construct(
        private I18nScopeDomainsInterface $scopeDomains,
        private I18nKeyInterface $i18nKey
    )
    {
    }

    public function isScopeCodeInUse(string $code): bool
    {
        return $this->scopeDomains->hasDomainsForScopeCode($code) || $this->i18nKey->hasKeysForScopeCode($code);
    }
}
