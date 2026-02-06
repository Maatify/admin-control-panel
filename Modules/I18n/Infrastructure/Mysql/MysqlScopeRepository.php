<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\ScopeRepositoryInterface;
use Maatify\I18n\DTO\ScopeDTO;
use Maatify\I18n\DTO\ScopeCollectionDTO;

final readonly class MysqlScopeRepository implements ScopeRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function getByCode(string $code): ?ScopeDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM i18n_scopes WHERE code = :code LIMIT 1'
        );

        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->map($row) : null;
    }

    public function listActive(): ScopeCollectionDTO
    {
        return $this->listByCondition('WHERE is_active = 1');
    }

    public function listAll(): ScopeCollectionDTO
    {
        return $this->listByCondition('');
    }

    private function listByCondition(string $where): ScopeCollectionDTO
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM i18n_scopes {$where} ORDER BY sort_order ASC"
        );

        if (!$stmt instanceof PDOStatement) {
            return new ScopeCollectionDTO([]);
        }

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row)) {
                $items[] = $this->map($row);
            }
        }

        return new ScopeCollectionDTO($items);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function map(array $row): ScopeDTO
    {
        $idRaw = $row['id'] ?? null;
        $sortOrderRaw = $row['sort_order'] ?? null;
        $isActiveRaw = $row['is_active'] ?? null;

        $codeRaw = $row['code'] ?? null;
        $nameRaw = $row['name'] ?? null;
        $createdAtRaw = $row['created_at'] ?? null;

        return new ScopeDTO(
            is_numeric($idRaw) ? (int) $idRaw : 0,
            is_string($codeRaw) ? $codeRaw : '',
            is_string($nameRaw) ? $nameRaw : '',
            is_string($row['description'] ?? null) ? $row['description'] : null,
            is_numeric($isActiveRaw) && (int)$isActiveRaw === 1,
            is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : 0,
            is_string($createdAtRaw) ? $createdAtRaw : ''
        );

    }

}
