<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Contract;

use Maatify\AuditTrail\DTO\AuditTrailQueryDTO;
use Maatify\AuditTrail\DTO\AuditTrailViewDTO;
use Maatify\AuditTrail\Exception\AuditTrailStorageException;

interface AuditTrailQueryInterface
{
    /**
     * Query audit trail records.
     *
     * @return array<AuditTrailViewDTO>
     * @throws AuditTrailStorageException
     */
    public function find(AuditTrailQueryDTO $query): array;
}
