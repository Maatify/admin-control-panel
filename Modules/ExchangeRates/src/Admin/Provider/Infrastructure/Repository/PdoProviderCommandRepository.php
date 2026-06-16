<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Infrastructure\Repository;

use Maatify\ExchangeRates\Admin\Provider\Command\CreateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Command\UpdateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderCommandRepositoryInterface;
use Maatify\ExchangeRates\Exception\ExchangeRatesCodeAlreadyExistsException;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;
use Maatify\ExchangeRates\Shared\Infrastructure\Persistence\Support\ScopedOrderingManager;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

final class PdoProviderCommandRepository implements ProviderCommandRepositoryInterface
{
    public function __construct(
        private readonly PDO                   $pdo,
        private readonly ScopedOrderingManager $orderingManager,
        private readonly ClockInterface        $clock,
    ) {}

    public function create(CreateProviderCommand $command): int
    {
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            'maa_er_providers',
            null,
            null
        );

        $stmt = $this->pdo->prepare(
            'INSERT INTO `maa_er_providers` (`name`, `code`, `description`, `is_active`, `display_order`)
             VALUES (:name, :code, :description, 1, :display_order)'
        );

        try {
            $stmt->execute([
                'name'          => $command->name,
                'code'          => $command->code,
                'description'   => $command->description,
                'display_order' => $displayOrder,
            ]);
        } catch (\PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw ExchangeRatesCodeAlreadyExistsException::withCode($command->code);
            }
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function update(UpdateProviderCommand $command): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `maa_er_providers`
                SET `name` = :name, `description` = :description
              WHERE `id` = :id
                AND `deleted_at` IS NULL'
        );

        $stmt->execute([
            'name'        => $command->name,
            'description' => $command->description,
            'id'          => $command->id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $id, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `maa_er_providers`
                SET `is_active` = :is_active
              WHERE `id` = :id
                AND `deleted_at` IS NULL'
        );

        $stmt->execute(['is_active' => $isActive ? 1 : 0, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateDisplayOrder(int $id, int $displayOrder): bool
    {
        $raw = $this->findRawById($id);
        if ($raw === null) {
            throw ExchangeRatesNotFoundException::withId($id);
        }

        // Guard: soft-deleted providers must not be reordered.
        $deletedAt = $raw['deleted_at'] ?? null;
        if (is_string($deletedAt)) {
            throw ExchangeRatesNotFoundException::withId($id);
        }

        $currentOrder = $raw['display_order'] ?? null;

        return $this->orderingManager->moveWithinScope(
            pdo:          $this->pdo,
            table:        'maa_er_providers',
            scopeCol:     null,
            scopeVal:     null,
            id:           $id,
            newOrder:     $displayOrder,
            currentOrder: (is_int($currentOrder) || is_string($currentOrder)) ? (int) $currentOrder : 0,
        );
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `maa_er_providers`
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
    //  Private helpers
    // =========================================================

    /** @return array<string, mixed>|null */
    private function findRawById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `display_order`, `deleted_at`
               FROM `maa_er_providers`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
