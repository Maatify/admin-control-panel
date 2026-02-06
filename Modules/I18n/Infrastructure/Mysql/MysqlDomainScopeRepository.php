<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\DomainScopeRepositoryInterface;

final readonly class MysqlDomainScopeRepository implements DomainScopeRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function isDomainAllowedForScope(
        string $scopeCode,
        string $domainCode
    ): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes
             WHERE scope_code = :scope
               AND domain_code = :domain
               AND is_active = 1
             LIMIT 1'
        );

        if (!$stmt instanceof PDOStatement) {
            return false;
        }

        $stmt->execute([
            'scope' => $scopeCode,
            'domain' => $domainCode,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function listDomainsForScope(string $scopeCode): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT domain_code
             FROM i18n_domain_scopes
             WHERE scope_code = :scope
               AND is_active = 1
             ORDER BY domain_code ASC'
        );

        if (!$stmt instanceof PDOStatement) {
            return [];
        }

        $stmt->execute(['scope' => $scopeCode]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
