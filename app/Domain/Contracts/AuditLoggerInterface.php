<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\LegacyAuditEventDTO;

interface AuditLoggerInterface
{
    public function log(LegacyAuditEventDTO $event): void;
}
