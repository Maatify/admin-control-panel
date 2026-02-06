<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 22:39
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\DomainRepositoryInterface;
use Maatify\I18n\Contract\DomainScopeRepositoryInterface;
use Maatify\I18n\DTO\DomainCollectionDTO;

final readonly class I18nDomainReadService
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository,
        private DomainScopeRepositoryInterface $domainScopeRepository
    )
    {
    }

    /**
     * List domains allowed for a given scope.
     *
     * - FAIL-SOFT by design
     * - No exceptions on invalid scope
     * - Used by Admin UI (select domain by scope)
     */
    public function listDomainsForScope(string $scopeCode): DomainCollectionDTO
    {
        // 1) Get allowed domain codes for scope
        $domainCodes = $this->domainScopeRepository
            ->listDomainsForScope($scopeCode);

        if ($domainCodes === []) {
            return new DomainCollectionDTO([]);
        }

        // 2) Resolve full domain records
        return $this->domainRepository
            ->listByCodes($domainCodes);
    }
}
