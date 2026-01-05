<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface TotpServiceInterface
{
    public function verify(string $secret, string $code): bool;
}
