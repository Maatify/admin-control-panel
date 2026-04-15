<?php

declare(strict_types=1);

namespace Maatify\Currency\Contract;

use Maatify\Currency\DTO\CurrencyDropdownCollectionDTO;
use Maatify\Currency\DTO\CurrencyDropdownItemDTO;

interface CurrencyDropdownQueryInterface
{
    /**
     * Finds one currency by ID for dropdown usage.
     * Returns null if the currency does not exist.
     */
    public function findById(int $id): ?CurrencyDropdownItemDTO;

    /**
     * Finds one currency by code for dropdown usage.
     * Returns null if the currency does not exist.
     */
    public function findByCode(string $code): ?CurrencyDropdownItemDTO;

    /**
     * Returns all currencies for dropdown usage.
     * Ordered by display_order ASC, then id ASC.
     */
    public function listAllForDropdown(): CurrencyDropdownCollectionDTO;

    /**
     * Returns active currencies only for dropdown usage.
     * Ordered by display_order ASC, then id ASC.
     */
    public function listActiveForDropdown(): CurrencyDropdownCollectionDTO;
}
