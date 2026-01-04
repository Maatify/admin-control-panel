<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminIdentifierLookupInterface
{
    public function findByBlindIndex(string $blindIndex): ?int;
}
