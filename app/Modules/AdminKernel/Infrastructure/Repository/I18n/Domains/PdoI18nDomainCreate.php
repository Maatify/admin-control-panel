<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 11:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Domains;

use Maatify\AdminKernel\Domain\DTO\I18n\Domains\I18nDomainCreateDTO;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainCreateInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nDomainCreate implements I18nDomainCreateInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(I18nDomainCreateDTO $dto): int
    {
        $newSort = $this->getNextSortOrder();

        $stmt = $this->pdo->prepare(
            'INSERT INTO i18n_domains (code, name, description, is_active, sort_order)
             VALUES (:code, :name, :description, :is_active, :sort_order)'
        );

        $ok = $stmt->execute([
            'code' => $dto->code,
            'name' => $dto->name,
            'description' => $dto->description,
            'is_active' => $dto->is_active,
            'sort_order' => $newSort,
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to create i18n Domain');
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Admin-only existence check.
     *
     * This method is intentionally placed here as a privileged
     * control-plane validation for admin create/update flows.
     */
    public function existsByCode(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domains WHERE code = :code LIMIT 1'
        );

        $stmt->execute([
            'code' => $code,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Determine the next sort_order value for a newly created scope.
     *
     * Sorting is managed internally and must NOT be provided by the client.
     * New scopes are always appended to the end of the list by assigning
     * (MAX(sort_order) + 1).
     *
     * This method serves as the single source of truth for initial ordering
     * and is intentionally shared by create and future reorder operations.
     */
    private function getNextSortOrder(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COALESCE(MAX(sort_order), 0) FROM i18n_domains'
        );

        if ($stmt === false) {
            // fail-safe default: first position
            return 1;
        }

        $stmt->execute();

        $max = $stmt->fetchColumn();

        return ((int)$max) + 1;
    }
}
