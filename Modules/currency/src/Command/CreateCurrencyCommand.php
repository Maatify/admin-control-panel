<?php

declare(strict_types=1);

namespace Maatify\Currency\Command;

/**
 * Carries all data required to persist a new currency.
 *
 * display_order = 0 → "append to end" signal.
 * The repository resolves this atomically via INSERT … SELECT MAX,
 * so the service does not need to compute the next position.
 */
final class CreateCurrencyCommand
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $symbol,
        public readonly bool   $isActive     = true,
        public readonly int    $displayOrder = 0,
    ) {}
}
