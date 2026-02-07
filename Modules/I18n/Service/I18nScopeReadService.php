<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 22:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\ScopeRepositoryInterface;
use Maatify\I18n\DTO\ScopeCollectionDTO;

final readonly class I18nScopeReadService
{
    public function __construct(
        private ScopeRepositoryInterface $scopeRepository
    )
    {
    }

    /**
     * Read-only list of all scopes.
     *
     * - FAIL-SOFT by design
     * - No policy enforcement here
     * - Kernel returns full truth; UI decides what to render
     */
    public function listScopes(): ScopeCollectionDTO
    {
        return $this->scopeRepository->listAll();
    }

    /**
     * List only ACTIVE scopes.
     *
     * - Intended for UI selectors and runtime filtering
     * - FAIL-SOFT by design
     * - No policy enforcement (policy applies at domain level)
     */
    public function listActiveScopes(): ScopeCollectionDTO
    {
        return $this->scopeRepository->listActive();
    }

}
