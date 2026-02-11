<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 14:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainListItemDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainsDropdownResponseDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsListReaderInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeDomainsListReader implements I18nScopeDomainsListReaderInterface
{
    public function __construct(private PDO $pdo) {}


    public function listByScopeId(int $scopeId): I18nScopeDomainsDropdownResponseDTO
    {
        $scopeCode = $this->resolveScopeCode($scopeId);

        $stmt = $this->pdo->prepare(
            "
            SELECT
                d.code,
                d.name
            FROM i18n_domains d
            INNER JOIN i18n_domain_scopes sd
                ON sd.domain_code = d.code
            WHERE sd.scope_code = :scope
            ORDER BY d.code ASC
            "
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare scope domains list query');
        }

        $stmt->execute(['scope' => $scopeCode]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {
            $items[] = new I18nScopeDomainListItemDTO(
                $row['code'],
                $row['name']
            );
        }

        return new I18nScopeDomainsDropdownResponseDTO($items);
    }

    private function resolveScopeCode(int $scopeId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT code FROM i18n_scopes WHERE id = :id'
        );

        $stmt->execute(['id' => $scopeId]);

        $code = $stmt->fetchColumn();

        if (!$code) {
            throw new EntityNotFoundException('scope not found', 'scopeId');
        }

        return (string)$code;
    }
}

