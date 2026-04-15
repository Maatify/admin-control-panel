<?php

declare(strict_types=1);

namespace Maatify\Currency\Infrastructure\Repository;

use Maatify\Currency\Contract\CurrencyDropdownQueryInterface;
use Maatify\Currency\DTO\CurrencyDropdownCollectionDTO;
use Maatify\Currency\DTO\CurrencyDropdownItemDTO;
use Maatify\Currency\Exception\CurrencyPersistenceException;
use PDO;
use PDOStatement;

final class PdoCurrencyDropdownQuery implements CurrencyDropdownQueryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function findById(int $id): ?CurrencyDropdownItemDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT
                c.`id`,
                c.`name`,
                c.`symbol`,
                c.`is_active`
            FROM `currencies` c
            WHERE c.`id` = :id
            LIMIT 1
        ');

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? $this->hydrateItem($row) : null;
    }

    public function findByCode(string $code): ?CurrencyDropdownItemDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT
                c.`id`,
                c.`name`,
                c.`symbol`,
                c.`is_active`
            FROM `currencies` c
            WHERE c.`code` = :code
            LIMIT 1
        ');

        $stmt->bindValue(':code', strtoupper(trim($code)));
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? $this->hydrateItem($row) : null;
    }

    public function listAllForDropdown(): CurrencyDropdownCollectionDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT
                c.`id`,
                c.`name`,
                c.`symbol`,
                c.`is_active`
            FROM `currencies` c
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ');

        $stmt->execute();

        $items = [];
        foreach ($this->fetchAllAssoc($stmt) as $row) {
            $item = $this->hydrateItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return new CurrencyDropdownCollectionDTO($items);
    }

    public function listActiveForDropdown(): CurrencyDropdownCollectionDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT
                c.`id`,
                c.`name`,
                c.`symbol`,
                c.`is_active`
            FROM `currencies` c
            WHERE c.`is_active` = 1
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ');

        $stmt->execute();

        $items = [];
        foreach ($this->fetchAllAssoc($stmt) as $row) {
            $item = $this->hydrateItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return new CurrencyDropdownCollectionDTO($items);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateItem(array $row): ?CurrencyDropdownItemDTO
    {
        $id = $row['id'] ?? null;
        $name = $row['name'] ?? null;
        $symbol = $row['symbol'] ?? null;
        $isActive = $row['is_active'] ?? null;

        if (!is_int($id) && !is_string($id)) {
            return null;
        }

        if (!is_string($name) || trim($name) === '') {
            return null;
        }

        if (!is_string($symbol) || trim($symbol) === '') {
            return null;
        }

        if (!is_int($isActive) && !is_string($isActive)) {
            return null;
        }

        return new CurrencyDropdownItemDTO(
            id: (int) $id,
            name: trim($name),
            symbol: trim($symbol),
            isActive: (int) $isActive,
        );
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw CurrencyPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAssoc(PDOStatement $stmt): ?array
    {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
