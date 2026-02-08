<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 21:56
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainUpdaterInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsWriterInterface;

final readonly class I18nScopeDomainsService
{
    public function __construct(
        private I18nScopeDomainsWriterInterface $writer,

        // Resolve scope_code from scope_id
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,

        private I18nDomainUpdaterInterface $domainReader,

        private I18nScopeDomainsInterface $reader
    )
    {
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
        $this->ensureDomainExists($domainCode);

        if($this->reader->isAssigned($scopeCode, $domainCode)){
            throw new InvalidOperationException(
                'domain',
                'assign',
                'already assigned to scope'
            );
        }

        $this->writer->assign($scopeCode, $domainCode);
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
        $this->ensureDomainExists($domainCode);

        if(!$this->reader->isAssigned($scopeCode, $domainCode)){
            throw new InvalidOperationException(
                'domain',
                'unassign',
                'not assigned to scope'
            );
        }
        $this->writer->unassign($scopeCode, $domainCode);
    }

    // ─────────────────────────────
    // Internals
    // ─────────────────────────────

    private function resolveScopeCode(int $scopeId): string
    {
        $scope = $this->scopeDetailsReader->getScopeDetailsById($scopeId);

        if ($scope->code === '') {
            throw new EntityNotFoundException('scope', $scopeId);
        }

        return $scope->code;
    }

    private function ensureDomainExists(string $domainCode): void
    {
        /**
         * Domain existence is resolved via the canonical Domain updater.
         * No list or pagination queries are used here.
         */

        if(!$this->domainReader->existsByCode($domainCode)){
            throw new EntityNotFoundException('domain', $domainCode);
        }
    }
}
