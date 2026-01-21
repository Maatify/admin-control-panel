<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\Security\SecurityEventStorageException;

interface SecurityEventLoggerInterface
{
    /**
     * @throws SecurityEventStorageException
     */
    public function log(SecurityEventDTO $event): void;
}
