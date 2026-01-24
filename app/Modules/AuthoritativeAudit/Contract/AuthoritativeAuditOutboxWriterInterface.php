<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Contract;

use Maatify\AuthoritativeAudit\DTO\AuthoritativeAuditOutboxWriteDTO;

interface AuthoritativeAuditOutboxWriterInterface
{
    public function write(AuthoritativeAuditOutboxWriteDTO $dto): void;
}
