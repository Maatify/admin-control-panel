<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\RateHistory\Infrastructure\Repository;

use Maatify\ExchangeRates\Admin\RateHistory\Contract\RateHistoryQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO;
use PDO;

final class PdoRateHistoryQueryRepository implements RateHistoryQueryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    // =========================================================
    //  List by rate_id
    // =========================================================

    /**
     * @return array{data: list<RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByRateId(int $rateId, int $page, int $perPage): array
    {
        // 1. Total for this rate_id
        $stmtTotal = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `maa_er_rate_history` WHERE `rate_id` = :rate_id'
        );
        $stmtTotal->execute(['rate_id' => $rateId]);
        $total = (int) $stmtTotal->fetchColumn();

        // 2. Filtered = same as total (no extra filters here)
        $filtered = $total;

        // 3. Data
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            'SELECT `h`.`id`, `h`.`rate_id`, `h`.`provider_id`,
                    `h`.`base_currency_code`, `h`.`target_currency_code`,
                    `h`.`rate`, `h`.`recorded_at`, `h`.`created_at`,
                    `p`.`name` AS `provider_name`
               FROM `maa_er_rate_history` `h`
         INNER JOIN `maa_er_providers` `p` ON `p`.`id` = `h`.`provider_id`
              WHERE `h`.`rate_id` = :rate_id
              ORDER BY `h`.`recorded_at` DESC
              LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':rate_id', $rateId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = $this->hydrateAll($rows);

        return [
            'data'       => $items,
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'filtered' => $filtered],
        ];
    }

    // =========================================================
    //  List by pair
    // =========================================================

    /**
     * @return array{data: list<RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByPair(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        int     $page,
        int     $perPage,
        ?int    $providerId,
    ): array {
        $base   = strtoupper($baseCurrencyCode);
        $target = strtoupper($targetCurrencyCode);

        $params = ['base_code' => $base, 'target_code' => $target];
        $where  = '`h`.`base_currency_code` = :base_code AND `h`.`target_currency_code` = :target_code';

        if ($providerId !== null) {
            $where             .= ' AND `h`.`provider_id` = :provider_id';
            $params['provider_id'] = $providerId;
        }

        // 1. Total
        $stmtTotal = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `maa_er_rate_history` `h` WHERE {$where}"
        );
        $stmtTotal->execute($params);
        $total    = (int) $stmtTotal->fetchColumn();
        $filtered = $total;

        // 2. Data
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            "SELECT `h`.`id`, `h`.`rate_id`, `h`.`provider_id`,
                    `h`.`base_currency_code`, `h`.`target_currency_code`,
                    `h`.`rate`, `h`.`recorded_at`, `h`.`created_at`,
                    `p`.`name` AS `provider_name`
               FROM `maa_er_rate_history` `h`
         INNER JOIN `maa_er_providers` `p` ON `p`.`id` = `h`.`provider_id`
              WHERE {$where}
              ORDER BY `h`.`recorded_at` DESC
              LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = $this->hydrateAll($rows);

        return [
            'data'       => $items,
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'filtered' => $filtered],
        ];
    }

    // =========================================================
    //  Point-in-time lookup
    // =========================================================

    public function findRateAt(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        string  $atDatetime,
        ?int    $providerId,
    ): ?string {
        $base   = strtoupper($baseCurrencyCode);
        $target = strtoupper($targetCurrencyCode);

        $params = [
            'base_code'   => $base,
            'target_code' => $target,
            'at_datetime' => $atDatetime,
        ];

        $extra = '';
        if ($providerId !== null) {
            $extra             = 'AND `provider_id` = :provider_id';
            $params['provider_id'] = $providerId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT `rate`
               FROM `maa_er_rate_history`
              WHERE `base_currency_code`   = :base_code
                AND `target_currency_code` = :target_code
                AND `recorded_at`         <= :at_datetime
                {$extra}
              ORDER BY `recorded_at` DESC
              LIMIT 1"
        );
        $stmt->execute($params);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $rate = $row['rate'] ?? null;

        return is_string($rate) ? $rate : null;
    }

    // =========================================================
    //  Hydration
    // =========================================================

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<RateHistoryListItemDTO>
     */
    private function hydrateAll(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $item = $this->hydrateListItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /** @param array<string, mixed> $row */
    private function hydrateListItem(array $row): ?RateHistoryListItemDTO
    {
        $id         = $row['id']          ?? null;
        $rateId     = $row['rate_id']     ?? null;
        $providerId = $row['provider_id'] ?? null;

        if (! (is_int($id) || is_string($id))) {
            return null;
        }

        return new RateHistoryListItemDTO(
            id:                 (int) $id,
            rateId:             (is_int($rateId)     || is_string($rateId))     ? (int) $rateId     : 0,
            providerId:         (is_int($providerId) || is_string($providerId)) ? (int) $providerId  : 0,
            providerName:       is_string($row['provider_name'] ?? null)            ? (string) $row['provider_name']        : '',
            baseCurrencyCode:   is_string($row['base_currency_code'] ?? null)       ? (string) $row['base_currency_code']   : '',
            targetCurrencyCode: is_string($row['target_currency_code'] ?? null)     ? (string) $row['target_currency_code'] : '',
            rate:               is_string($row['rate'] ?? null)                     ? (string) $row['rate']                 : '0',
            recordedAt:         is_string($row['recorded_at'] ?? null)              ? (string) $row['recorded_at']          : '',
            createdAt:          is_string($row['created_at'] ?? null)               ? (string) $row['created_at']           : '',
        );
    }
}
