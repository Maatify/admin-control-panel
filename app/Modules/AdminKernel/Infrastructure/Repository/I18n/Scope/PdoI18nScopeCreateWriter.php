<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopeCreateDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeCreateWriterInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeCreateWriter implements I18nScopeCreateWriterInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function create(I18nScopeCreateDTO $dto): int
    {
        $newSort = $this->getNextSortOrder();

        $stmt = $this->pdo->prepare(
            'INSERT INTO i18n_scopes (code, name, description, is_active, sort_order)
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
            throw new RuntimeException('Failed to create i18n scope');
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
            'SELECT 1 FROM i18n_scopes WHERE code = :code LIMIT 1'
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
            'SELECT COALESCE(MAX(sort_order), 0) FROM i18n_scopes'
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


