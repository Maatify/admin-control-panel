<?php

declare(strict_types=1);

namespace Maatify\Currency\Command;

/**
 * Toggles the active / inactive flag for a currency.
 */
final class UpdateCurrencyStatusCommand
{
    public function __construct(
        public readonly int  $id,
        public readonly bool $isActive,
    ) {}
}
