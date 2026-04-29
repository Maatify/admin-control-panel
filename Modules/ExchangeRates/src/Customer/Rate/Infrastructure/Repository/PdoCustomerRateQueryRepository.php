<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Customer\Rate\Infrastructure\Repository;

use Maatify\ExchangeRates\Customer\Rate\Contract\CustomerRateQueryRepositoryInterface;
use Maatify\ExchangeRates\Customer\Rate\DTO\CustomerRateCollectionDTO;
use Maatify\ExchangeRates\Customer\Rate\DTO\CustomerRateDTO;
use PDO;

/**
 * Customer-facing rate queries.
 *
 * Only exposes rates where:
 *   - rate.is_active    = 1
 *   - rate.deleted_at   IS NULL
 *   - provider.is_active = 1          ← guards against deactivated providers
 *   - provider.deleted_at IS NULL     ← guards against soft-deleted providers
 *
 * Admin fields (deleted_at, updated_at) are never returned.
 *
 * providerId = null:
 *   Returns the first matching rate ordered by provider.display_order ASC,
 *   then rate.display_order ASC. The provider with the lowest display_order
 *   that has an active rate for the requested pair wins.
 */
final class PdoCustomerRateQueryRepository implements CustomerRateQueryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function findCurrentRate(
        string $baseCurrencyCode,
        string $targetCurrencyCode,
        ?int   $providerId,
    ): ?string {
        $base   = strtoupper($baseCurrencyCode);
        $target = strtoupper($targetCurrencyCode);

        $params = [
            'base_code'   => $base,
            'target_code' => $target,
        ];

        $providerFilter = '';
        if ($providerId !== null) {
            $providerFilter        = 'AND `r`.`provider_id` = :provider_id';
            $params['provider_id'] = $providerId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT `r`.`rate`
               FROM `maa_er_rates` `r`
         INNER JOIN `maa_er_providers` `p`
                 ON `p`.`id`         = `r`.`provider_id`
                AND `p`.`is_active`  = 1
                AND `p`.`deleted_at` IS NULL
              WHERE `r`.`base_currency_code`   = :base_code
                AND `r`.`target_currency_code` = :target_code
                AND `r`.`is_active`            = 1
                AND `r`.`deleted_at`           IS NULL
                {$providerFilter}
              ORDER BY `p`.`display_order` ASC, `r`.`display_order` ASC
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

    public function listActiveForBase(
        string $baseCurrencyCode,
        ?int   $providerId,
    ): CustomerRateCollectionDTO {
        $base   = strtoupper($baseCurrencyCode);
        $params = ['base_code' => $base];

        $providerFilter = '';
        if ($providerId !== null) {
            $providerFilter        = 'AND `r`.`provider_id` = :provider_id';
            $params['provider_id'] = $providerId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT `r`.`provider_id`, `r`.`base_currency_code`,
                    `r`.`target_currency_code`, `r`.`rate`
               FROM `maa_er_rates` `r`
         INNER JOIN `maa_er_providers` `p`
                 ON `p`.`id`         = `r`.`provider_id`
                AND `p`.`is_active`  = 1
                AND `p`.`deleted_at` IS NULL
              WHERE `r`.`base_currency_code` = :base_code
                AND `r`.`is_active`          = 1
                AND `r`.`deleted_at`         IS NULL
                {$providerFilter}
              ORDER BY `p`.`display_order` ASC, `r`.`display_order` ASC"
        );
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $item = $this->hydrateListItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return new CustomerRateCollectionDTO($items);
    }

    // =========================================================
    //  Hydration
    // =========================================================

    /** @param array<string, mixed> $row */
    private function hydrateListItem(array $row): ?CustomerRateDTO
    {
        $providerId = $row['provider_id'] ?? null;

        if (! is_string($row['base_currency_code'] ?? null)) {
            return null;
        }

        return new CustomerRateDTO(
            baseCurrencyCode:   (string) $row['base_currency_code'],
            targetCurrencyCode: is_string($row['target_currency_code'] ?? null) ? (string) $row['target_currency_code'] : '',
            rate:               is_string($row['rate'] ?? null)                 ? (string) $row['rate']                 : '0',
            providerId:         (is_int($providerId) || is_string($providerId)) ? (int) $providerId : 0,
        );
    }
}
