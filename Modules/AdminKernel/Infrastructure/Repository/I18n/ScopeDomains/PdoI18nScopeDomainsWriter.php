<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 21:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsWriterInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeDomainsWriter implements I18nScopeDomainsWriterInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function assign(string $scopeCode, string $domainCode): void
    {
        /**
         * Idempotent insert.
         * UNIQUE(scope_code, domain_code) guarantees no duplicates.
         */
        $stmt = $this->pdo->prepare(
            '
            INSERT INTO i18n_domain_scopes (scope_code, domain_code)
            VALUES (:scope_code, :domain_code)
            ON DUPLICATE KEY UPDATE scope_code = scope_code
            '
        );

        $ok = $stmt->execute([
            'scope_code'  => $scopeCode,
            'domain_code' => $domainCode,
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to assign domain to scope');
        }
    }

    public function unassign(string $scopeCode, string $domainCode): void
    {
        /**
         * Idempotent delete.
         * If row does not exist → 0 rows affected → OK.
         */
        $stmt = $this->pdo->prepare(
            '
            DELETE FROM i18n_domain_scopes
            WHERE scope_code = :scope_code
              AND domain_code = :domain_code
            '
        );

        $ok = $stmt->execute([
            'scope_code'  => $scopeCode,
            'domain_code' => $domainCode,
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to unassign domain from scope');
        }
    }
}
