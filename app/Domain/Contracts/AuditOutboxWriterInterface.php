<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AuditEventDTO;

interface AuditOutboxWriterInterface
{
    public function write(AuditEventDTO $event): void;
}
