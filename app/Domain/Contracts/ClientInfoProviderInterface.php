<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface ClientInfoProviderInterface
{
    public function getIpAddress(): ?string;
    public function getUserAgent(): ?string;
}
