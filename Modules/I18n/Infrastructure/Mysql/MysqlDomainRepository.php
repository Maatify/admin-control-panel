<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\DomainRepositoryInterface;
use Maatify\I18n\DTO\DomainDTO;
use Maatify\I18n\DTO\DomainCollectionDTO;

final readonly class MysqlDomainRepository implements DomainRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getByCode(string $code): ?DomainDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, description, is_active, sort_order, created_at
             FROM i18n_domains
             WHERE code = :code
             LIMIT 1'
        );

        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute(['code' => $code]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    public function listActive(): DomainCollectionDTO
    {
        return $this->listByCondition('WHERE is_active = 1');
    }

    public function listAll(): DomainCollectionDTO
    {
        return $this->listByCondition('');
    }

    private function listByCondition(string $whereClause): DomainCollectionDTO
    {
        $sql = 'SELECT id, code, name, description, is_active, sort_order, created_at
                FROM i18n_domains '
               . $whereClause .
               ' ORDER BY sort_order ASC, code ASC';

        $stmt = $this->pdo->query($sql);
        if (!$stmt instanceof PDOStatement) {
            return new DomainCollectionDTO([]);
        }

        $items = [];

        while (true) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                break;
            }

            $dto = $this->mapRowToDTO($row);
            if ($dto !== null) {
                $items[] = $dto;
            }
        }

        return new DomainCollectionDTO($items);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDTO(array $row): ?DomainDTO
    {
        $id = $row['id'] ?? null;
        $code = $row['code'] ?? null;
        $name = $row['name'] ?? null;
        $createdAt = $row['created_at'] ?? null;

        if (!is_numeric($id) || !is_string($code) || !is_string($name) || !is_string($createdAt)) {
            return null;
        }

        $sortOrderRaw = $row['sort_order'] ?? null;
        $sortOrder = is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : 0;

        $isActiveRaw = $row['is_active'] ?? null;
        $isActive = is_numeric($isActiveRaw) && (int)$isActiveRaw === 1;

        return new DomainDTO(
            (int) $id,
            $code,
            $name,
            is_string($row['description'] ?? null) ? $row['description'] : null,
            $isActive,
            $sortOrder,
            $createdAt
        );
    }

    /**
     * @param list<string> $codes
     */
    public function listByCodes(array $codes): DomainCollectionDTO
    {
        if ($codes === []) {
            return new DomainCollectionDTO([]);
        }

        // Normalize & de-duplicate
        $codes = array_values(array_unique(array_filter($codes, 'is_string')));

        if ($codes === []) {
            return new DomainCollectionDTO([]);
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($codes), '?')
        );

        $sql = "
        SELECT id, code, name, description, is_active, sort_order, created_at
        FROM i18n_domains
        WHERE code IN ($placeholders)
              AND is_active = 1
        ORDER BY sort_order ASC, code ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return new DomainCollectionDTO([]);
        }

        $stmt->execute($codes);

        $items = [];

        while (true) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                break;
            }

            $dto = $this->mapRowToDTO($row);
            if ($dto !== null) {
                $items[] = $dto;
            }
        }

        return new DomainCollectionDTO($items);
    }

}
