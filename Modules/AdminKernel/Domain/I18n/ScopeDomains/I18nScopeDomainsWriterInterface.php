<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 21:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains;

interface I18nScopeDomainsWriterInterface
{
    /**
     * Assign a domain to a scope.
     *
     * Idempotent:
     * - If already assigned, MUST NOT throw.
     */
    public function assign(string $scopeCode, string $domainCode): void;

    /**
     * Unassign a domain from a scope.
     *
     * Idempotent:
     * - If relation does not exist, MUST NOT throw.
     */
    public function unassign(string $scopeCode, string $domainCode): void;
}
