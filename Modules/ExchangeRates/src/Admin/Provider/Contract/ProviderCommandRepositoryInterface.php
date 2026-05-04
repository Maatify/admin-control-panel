<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Contract;

use Maatify\ExchangeRates\Admin\Provider\Command\CreateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Command\UpdateProviderCommand;

interface ProviderCommandRepositoryInterface
{
    /**
     * Insert a new provider. Auto-assigns display_order (global scope).
     * Returns the new row id.
     */
    public function create(CreateProviderCommand $command): int;

    /**
     * Update name and description. Returns true if a row was changed.
     */
    public function update(UpdateProviderCommand $command): bool;

    /**
     * Toggle is_active flag. Returns true if a row was changed.
     */
    public function updateStatus(int $id, bool $isActive): bool;

    /**
     * Move display_order (global scope — providers are not scoped).
     * Returns true if a row was changed.
     */
    public function updateDisplayOrder(int $id, int $displayOrder): bool;

    /**
     * Soft-delete: set deleted_at = NOW(). Returns true if a row was changed.
     */
    public function softDelete(int $id): bool;
}
