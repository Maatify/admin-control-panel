<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Infrastructure\Repository;

use Maatify\ExchangeRates\Admin\Rate\Command\CreateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Command\UpdateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Contract\RateCommandRepositoryInterface;
use Maatify\ExchangeRates\Exception\ExchangeRatesCodeAlreadyExistsException;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;
use Maatify\ExchangeRates\Shared\Infrastructure\Persistence\Support\ScopedOrderingManager;
use Maatify\ExchangeRates\Shared\Infrastructure\Support\RateHistoryWriter;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

final class PdoRateCommandRepository implements RateCommandRepositoryInterface
{
    public function __construct(
        private readonly PDO                   $pdo,
        private readonly RateHistoryWriter      $historyWriter,
        private readonly ScopedOrderingManager  $orderingManager,
        private readonly ClockInterface         $clock,
    ) {}

    // =========================================================
    //  Create
    // =========================================================

    public function create(CreateRateCommand $command): int
    {
        $this->pdo->beginTransaction();

        try {
            $displayOrder = $this->orderingManager->getNextPosition(
                $this->pdo,
                'maa_er_rates',
                'provider_id',
                $command->providerId
            );

            $stmt = $this->pdo->prepare(
                'INSERT INTO `maa_er_rates`
                    (`provider_id`, `base_currency_code`, `target_currency_code`,
                     `rate`, `is_active`, `display_order`)
                 VALUES
                    (:provider_id, :base_code, :target_code, :rate, 1, :display_order)'
            );

            try {
                $stmt->execute([
                    'provider_id'   => $command->providerId,
                    'base_code'     => $command->baseCurrencyCode,
                    'target_code'   => $command->targetCurrencyCode,
                    'rate'          => $command->rate,
                    'display_order' => $displayOrder,
                ]);
            } catch (\PDOException $e) {
                if (str_starts_with((string) $e->getCode(), '23')) {
                    throw ExchangeRatesCodeAlreadyExistsException::withPair(
                        $command->baseCurrencyCode,
                        $command->targetCurrencyCode,
                        $command->providerId
                    );
                }
                throw $e;
            }

            $newId = (int) $this->pdo->lastInsertId();

            $this->historyWriter->write(
                rateId:     $newId,
                providerId: $command->providerId,
                baseCode:   $command->baseCurrencyCode,
                targetCode: $command->targetCurrencyCode,
                rate:       $command->rate,
                recordedAt: $command->recordedAt,
            );

            $this->pdo->commit();

            return $newId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // =========================================================
    //  Update rate value
    // =========================================================

    public function updateRate(UpdateRateCommand $command): bool
    {
        $raw = $this->findRawById($command->id);
        if ($raw === null) {
            throw ExchangeRatesNotFoundException::withId($command->id);
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE `maa_er_rates`
                    SET `rate` = :rate
                  WHERE `id` = :id
                    AND `deleted_at` IS NULL'
            );
            $stmt->execute(['rate' => $command->rate, 'id' => $command->id]);

            $changed = $stmt->rowCount() > 0;

            if ($changed) {
                // Append the new submitted rate value to history with recorded_at.
                // The previous value is already in history from its own create or update call.
                $providerId = $raw['provider_id']          ?? null;
                $baseCode   = $raw['base_currency_code']   ?? null;
                $targetCode = $raw['target_currency_code'] ?? null;

                $this->historyWriter->write(
                    rateId:     $command->id,
                    providerId: (is_int($providerId) || is_string($providerId)) ? (int) $providerId : 0,
                    baseCode:   is_string($baseCode)   ? $baseCode   : '',
                    targetCode: is_string($targetCode) ? $targetCode : '',
                    rate:       $command->rate,
                    recordedAt: $command->recordedAt,
                );
            }

            $this->pdo->commit();

            return $changed;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // =========================================================
    //  Status
    // =========================================================

    public function updateStatus(int $id, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `maa_er_rates`
                SET `is_active` = :is_active
              WHERE `id` = :id
                AND `deleted_at` IS NULL'
        );
        $stmt->execute(['is_active' => $isActive ? 1 : 0, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // =========================================================
    //  Display order
    // =========================================================

    public function updateDisplayOrder(int $id, int $displayOrder): bool
    {
        $raw = $this->findRawById($id);
        if ($raw === null) {
            throw ExchangeRatesNotFoundException::withId($id);
        }

        // Guard: soft-deleted rates must not be reordered.
        $deletedAt = $raw['deleted_at'] ?? null;
        if (is_string($deletedAt)) {
            throw ExchangeRatesNotFoundException::withId($id);
        }

        $providerId   = $raw['provider_id']   ?? null;
        $currentOrder = $raw['display_order'] ?? null;

        return $this->orderingManager->moveWithinScope(
            pdo:          $this->pdo,
            table:        'maa_er_rates',
            scopeCol:     'provider_id',
            scopeVal:     (is_int($providerId) || is_string($providerId)) ? (int) $providerId : 0,
            id:           $id,
            newOrder:     $displayOrder,
            currentOrder: (is_int($currentOrder) || is_string($currentOrder)) ? (int) $currentOrder : 0,
        );
    }

    // =========================================================
    //  Soft delete
    // =========================================================

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `maa_er_rates`
                SET `deleted_at` = :now
              WHERE `id` = :id
                AND `deleted_at` IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'now' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0;
    }

    // =========================================================
    //  Internal raw lookup
    // =========================================================

    /** @return array<string, mixed>|null */
    private function findRawById(int $id): ?array
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
}
