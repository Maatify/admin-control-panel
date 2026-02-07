<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Writer\I18n;

use Maatify\AdminKernel\Domain\DTO\I18nScopes\I18nScopeCreateDTO;
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
        $stmt = $this->pdo->prepare(
            'INSERT INTO i18n_scopes (code, name, description, is_active, sort_order)
             VALUES (:code, :name, :description, :is_active, :sort_order)'
        );

        $ok = $stmt->execute([
            'code' => $dto->code,
            'name' => $dto->name,
            'description' => $dto->description,
            'is_active' => $dto->is_active,
            'sort_order' => $dto->sort_order,
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
}


