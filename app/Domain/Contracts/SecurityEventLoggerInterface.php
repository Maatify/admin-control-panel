<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\SecurityEventDTO;

interface SecurityEventLoggerInterface
{
    public function log(SecurityEventDTO $event): void;
}
