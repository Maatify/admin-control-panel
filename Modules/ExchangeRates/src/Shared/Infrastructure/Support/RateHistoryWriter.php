<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Shared\Infrastructure\Support;

use PDO;

/**
 * Writes a snapshot to maa_er_rate_history.
 *
 * Called by PdoRateCommandRepository on every rate insert / update.
 * This is a module-local SQL support builder — not a public service.
 *
 * Rules:
 *   - Rows are append-only: never updated, never deleted
 *   - recorded_at is set by the caller (supports backfill from provider feeds)
 *   - When recordedAt is null, defaults to the current datetime
 */
final class RateHistoryWriter
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Append a history snapshot.
     *
     * @param  int    $rateId      FK → maa_er_rates.id
     * @param  int    $providerId  Denormalised
     * @param  string $baseCode    ISO 4217 CHAR(3)
     * @param  string $targetCode  ISO 4217 CHAR(3)
     * @param  string $rate        Decimal string e.g. '48.7500000000'
     * @param  string|null $recordedAt  'Y-m-d H:i:s' — null = now()
     * @return int   Inserted history row id
     */
    public function write(
        int $rateId,
        int $providerId,
        string $baseCode,
        string $targetCode,
        string $rate,
        ?string $recordedAt = null,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `maa_er_rate_history`
                (`rate_id`, `provider_id`, `base_currency_code`, `target_currency_code`, `rate`, `recorded_at`)
             VALUES
                (:rate_id, :provider_id, :base_code, :target_code, :rate, :recorded_at)'
        );

        $stmt->execute([
            'rate_id'     => $rateId,
            'provider_id' => $providerId,
            'base_code'   => strtoupper($baseCode),
            'target_code' => strtoupper($targetCode),
            'rate'        => $rate,
            'recorded_at' => $recordedAt ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
