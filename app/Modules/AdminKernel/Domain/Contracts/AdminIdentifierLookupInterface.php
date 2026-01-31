<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\AdminEmailIdentifierDTO;

interface AdminIdentifierLookupInterface
{
    public function findByBlindIndex(string $blindIndex): ?AdminEmailIdentifierDTO;
}
