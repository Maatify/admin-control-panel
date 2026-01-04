<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AuditEventDTO;

interface AuditLoggerInterface
{
    public function log(AuditEventDTO $event): void;
}
