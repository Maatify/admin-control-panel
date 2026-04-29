<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Contract;

use Maatify\ExchangeRates\Admin\Rate\Command\CreateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Command\UpdateRateCommand;

interface RateCommandRepositoryInterface
{
    /**
     * Insert a new rate, archive it to history, auto-assign display_order.
     * Returns new row id.
     */
    public function create(CreateRateCommand $command): int;

    /**
     * Update the rate value and archive the new value to history.
     * Returns true if a row was changed.
     */
    public function updateRate(UpdateRateCommand $command): bool;

    /**
     * Toggle is_active. Returns true if changed.
     */
    public function updateStatus(int $id, bool $isActive): bool;

    /**
     * Move display_order within provider scope. Returns true if changed.
     */
    public function updateDisplayOrder(int $id, int $displayOrder): bool;

    /**
     * Soft-delete: set deleted_at = NOW(). Returns true if changed.
     */
    public function softDelete(int $id): bool;
}
