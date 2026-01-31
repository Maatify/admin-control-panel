<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

interface TotpServiceInterface
{
    public function generateSecret(): string;

    public function verify(string $secret, string $code): bool;

    public function generateProvisioningUri(
        string $issuer,
        string $accountName,
        string $secret
    ): string;

}
