<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 18:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;
use PDO;

final readonly class I18nScopeDomainsRepository implements I18nScopeDomainsInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hasScopeForDomainCode(string $domainCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes WHERE domain_code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $domainCode]);

        return $stmt->fetchColumn() !== false;
    }

    public function hasDomainsForScopeCode(string $scopeCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes WHERE scope_code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $scopeCode]);

        return $stmt->fetchColumn() !== false;
    }

    public function isAssigned(string $scopeCode, string $domainCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes WHERE scope_code = :scope_code AND domain_code = :domain_code LIMIT 1'
        );
        $stmt->execute(['scope_code' => $scopeCode, 'domain_code' => $domainCode]);

        return $stmt->fetchColumn() !== false;
    }
}
