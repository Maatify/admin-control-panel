<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Infrastructure\Repository;

use Maatify\ExchangeRates\Admin\Rate\Contract\RateQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Rate\DTO\RateDTO;
use Maatify\ExchangeRates\Admin\Rate\DTO\RateListItemDTO;
use PDO;

final class PdoRateQueryRepository implements RateQueryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    // =========================================================
    //  Single-row
    // =========================================================

    public function findById(int $id): ?RateDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT `r`.`id`, `r`.`provider_id`, `r`.`base_currency_code`,
                    `r`.`target_currency_code`, `r`.`rate`, `r`.`is_active`,
                    `r`.`display_order`, `r`.`created_at`, `r`.`updated_at`, `r`.`deleted_at`,
                    `p`.`name` AS `provider_name`, `p`.`code` AS `provider_code`
               FROM `maa_er_rates` `r`
         INNER JOIN `maa_er_providers` `p` ON `p`.`id` = `r`.`provider_id`
              WHERE `r`.`id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateDetail($row);
    }

    /** @return array<string, mixed>|null */
    public function findRawById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `provider_id`, `base_currency_code`, `target_currency_code`,
                    `rate`, `display_order`, `deleted_at`
               FROM `maa_er_rates`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    // =========================================================
    //  Paginated list
    // =========================================================

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<RateListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        // 1. Total
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM `maa_er_rates`');
        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to count maa_er_rates');
        }
        $total = (int) $stmtTotal->fetchColumn();

        // 2. WHERE
        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            // Each column gets its own named placeholder — PDO does not allow
            // reuse of the same placeholder within a single statement.
            $where[]               = '(`r`.`base_currency_code` LIKE :global_base OR `r`.`target_currency_code` LIKE :global_target)';
            $searchVal             = '%' . trim($globalSearch) . '%';
            $params['global_base'] = $searchVal;
            $params['global_target'] = $searchVal;
        }

        if (isset($columnFilters['provider_id'])) {
            $where[]               = '`r`.`provider_id` = :provider_id';
            $params['provider_id'] = (int) $columnFilters['provider_id'];
        }

        if (isset($columnFilters['is_active'])) {
            $where[]             = '`r`.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['base_currency_code'])) {
            $where[]             = '`r`.`base_currency_code` = :base_code';
            $params['base_code'] = strtoupper((string) $columnFilters['base_currency_code']);
        }

        if (array_key_exists('deleted', $columnFilters) && $columnFilters['deleted'] !== null) {
            $deletedFilter = (int) $columnFilters['deleted'];

            $where[] = $deletedFilter === 1
                ? 'r.deleted_at IS NOT NULL'
                : 'r.deleted_at IS NULL';
        } else {
            $where[] = 'r.deleted_at IS NULL';
        }

        // $where always contains at least the deleted_at condition — never empty.
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // 3. Filtered count
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(`r`.`id`)
               FROM `maa_er_rates` `r`
         INNER JOIN `maa_er_providers` `p` ON `p`.`id` = `r`.`provider_id`
               {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // 4. Data
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            "SELECT `r`.`id`, `r`.`provider_id`, `r`.`base_currency_code`,
                    `r`.`target_currency_code`, `r`.`rate`, `r`.`is_active`, `r`.`display_order`,
                    `p`.`name` AS `provider_name`
               FROM `maa_er_rates` `r`
         INNER JOIN `maa_er_providers` `p` ON `p`.`id` = `r`.`provider_id`
               {$whereSql}
              ORDER BY `r`.`display_order` ASC, `r`.`id` DESC
              LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $item = $this->hydrateListItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return [
            'data'       => $items,
            'pagination' => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // =========================================================
    //  Hydration
    // =========================================================

    /** @param array<string, mixed> $row */
    private function hydrateDetail(array $row): RateDTO
    {
        $id           = $row['id']            ?? null;
        $providerId   = $row['provider_id']   ?? null;
        $isActive     = $row['is_active']     ?? null;
        $displayOrder = $row['display_order'] ?? null;
        $updatedAt    = $row['updated_at']    ?? null;
        $deletedAt    = $row['deleted_at']    ?? null;

        return new RateDTO(
            id:                 (is_int($id)           || is_string($id))           ? (int) $id           : 0,
            providerId:         (is_int($providerId)   || is_string($providerId))   ? (int) $providerId   : 0,
            providerName:       is_string($row['provider_name'] ?? null) ? (string) $row['provider_name'] : '',
            providerCode:       is_string($row['provider_code'] ?? null) ? (string) $row['provider_code'] : '',
            baseCurrencyCode:   is_string($row['base_currency_code'] ?? null)   ? (string) $row['base_currency_code']   : '',
            targetCurrencyCode: is_string($row['target_currency_code'] ?? null) ? (string) $row['target_currency_code'] : '',
            rate:               is_string($row['rate'] ?? null) ? (string) $row['rate'] : '0',
            isActive:           (is_int($isActive)     || is_string($isActive))     && (int) $isActive     === 1,
            displayOrder:       (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder  : 0,
            createdAt:          is_string($row['created_at'] ?? null) ? (string) $row['created_at'] : '',
            updatedAt:          is_string($updatedAt) ? $updatedAt : null,
            deletedAt:          is_string($deletedAt) ? $deletedAt : null,
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateListItem(array $row): ?RateListItemDTO
    {
        $id           = $row['id']            ?? null;
        $providerId   = $row['provider_id']   ?? null;
        $isActive     = $row['is_active']     ?? null;
        $displayOrder = $row['display_order'] ?? null;

        if (! (is_int($id) || is_string($id))) {
            return null;
        }

        return new RateListItemDTO(
            id:                 (int) $id,
            providerId:         (is_int($providerId)   || is_string($providerId))   ? (int) $providerId   : 0,
            providerName:       is_string($row['provider_name'] ?? null) ? (string) $row['provider_name'] : '',
            baseCurrencyCode:   is_string($row['base_currency_code'] ?? null)   ? (string) $row['base_currency_code']   : '',
            targetCurrencyCode: is_string($row['target_currency_code'] ?? null) ? (string) $row['target_currency_code'] : '',
            rate:               is_string($row['rate'] ?? null) ? (string) $row['rate'] : '0',
            isActive:           (is_int($isActive)     || is_string($isActive))     && (int) $isActive     === 1,
            displayOrder:       (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder  : 0,
        );
    }
}
