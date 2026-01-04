<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Contracts\ClientInfoProviderInterface;

class WebClientInfoProvider implements ClientInfoProviderInterface
{
    public function getIpAddress(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
