<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Service;

use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
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

    public function generateProvisioningUri(
        string $issuer,
        string $accountName,
        string $secret
    ): string {
        $label = rawurlencode($issuer . ':' . $accountName);

        $query = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => 6,
            'period'    => 30,
        ]);

        return sprintf(
            'otpauth://totp/%s?%s',
            $label,
            $query
        );
    }
}
