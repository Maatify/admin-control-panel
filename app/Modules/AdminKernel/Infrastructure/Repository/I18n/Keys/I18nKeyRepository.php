<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 18:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Keys;

use PDO;

final readonly class I18nKeyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hasKeysForDomainCode(string $domainCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_keys WHERE domain = :code LIMIT 1'
        );
        $stmt->execute(['code' => $domainCode]);

        return $stmt->fetchColumn() !== false;
    }

    public function hasKeysForScopeCode(string $scopeCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_keys WHERE scope = :code LIMIT 1'
        );
        $stmt->execute(['code' => $scopeCode]);

        return $stmt->fetchColumn() !== false;
    }
}
