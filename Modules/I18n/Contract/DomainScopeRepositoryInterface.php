<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

interface DomainScopeRepositoryInterface
{
    // ===== Read (Safe / Runtime) =====
    public function isDomainAllowedForScope(
        string $scopeCode,
        string $domainCode
    ): bool;

    /**
     * @return array<string> List of domain codes allowed for the given scope
     */
    public function listDomainsForScope(string $scopeCode): array;

}
