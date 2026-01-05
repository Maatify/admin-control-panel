<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Contracts\TotpServiceInterface;
use PragmaRX\Google2FA\Google2FA;

class Google2faTotpService implements TotpServiceInterface
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return (string)$this->google2fa->generateSecretKey();
    }

    public function verify(string $secret, string $code): bool
    {
        return (bool)$this->google2fa->verifyKey($secret, $code);
    }
}
