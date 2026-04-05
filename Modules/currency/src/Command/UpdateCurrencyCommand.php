<?php

declare(strict_types=1);

namespace Maatify\Currency\Command;

/**
 * Carries all data required to update an existing currency (full replace).
 * If display_order differs from the stored value the repository will
 * automatically re-sort the surrounding rows inside a transaction.
 */
final class UpdateCurrencyCommand
{
    public function __construct(
        public readonly int    $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $symbol,
        public readonly bool   $isActive,
        public readonly int    $displayOrder,
    ) {}
}
