<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Service;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\I18n\Exception\DomainNotFoundException;
use Maatify\I18n\Exception\DomainScopeAlreadyAssignedException;
use Maatify\I18n\Exception\DomainScopeNotAssignedException;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Management\Service\I18nManagementReadService;
use Maatify\I18n\Management\Service\I18nScopeDomainManagementService;

/**
 * Admin scope <-> domain assignment. The Admin route carries the scope id (Host
 * navigation identity); the I18n package works by code, takes the deterministic
 * locks and owns the duplicate / not-assigned decisions.
 */
final readonly class I18nScopeDomainsService
{
    public function __construct(
        private I18nScopeDomainManagementService $management,
        private I18nManagementReadService $read,
    ) {
    }

    /**
     * Assign domain to scope.
     *
     * @throws EntityNotFoundException
     * @throws InvalidOperationException
     */
    public function assign(int $scopeId, string $domainCode): void
    {
        $scopeCode = $this->resolveScopeCode($scopeId);

        try {
            $this->management->assign($scopeCode, $domainCode);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope', $scopeId);
        } catch (DomainNotFoundException) {
            throw new EntityNotFoundException('domain', $domainCode);
        } catch (DomainScopeAlreadyAssignedException) {
            throw new InvalidOperationException('domain', 'assign', 'already assigned to scope');
        }
    }

    /**
     * Unassign domain from scope.
     *
     * @throws EntityNotFoundException
     * @throws InvalidOperationException
     */
    public function unassign(int $scopeId, string $domainCode): void
    {
        $scopeCode = $this->resolveScopeCode($scopeId);

        try {
            $this->management->unassign($scopeCode, $domainCode);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope', $scopeId);
        } catch (DomainNotFoundException) {
            throw new EntityNotFoundException('domain', $domainCode);
        } catch (DomainScopeNotAssignedException) {
            throw new InvalidOperationException('domain', 'unassign', 'not assigned to scope');
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    private function resolveScopeCode(int $scopeId): string
    {
        try {
            return $this->read->getScope($scopeId)->code;
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope', $scopeId);
        }
    }
}
