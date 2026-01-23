<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Contract;

use Maatify\AuditTrail\DTO\AuditTrailRecordDTO;
use Maatify\AuditTrail\Exception\AuditTrailStorageException;

interface AuditTrailLoggerInterface
{
    /**
     * Persist an audit trail record.
     *
     * @throws AuditTrailStorageException
     */
    public function write(AuditTrailRecordDTO $record): void;
}
