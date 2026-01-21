<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AuditEventDTO;
use App\Domain\Exception\Audit\AuditStorageException;

/**
 * Authoritative source for security-critical audit events.
 * Implementations MUST enforce an active transaction.
 * Used ONLY for: Security events, Privilege changes, Sessions, Step-Up.
 */
interface AuthoritativeSecurityAuditWriterInterface
{
    /**
     * @throws AuditStorageException
     */
    public function write(AuditEventDTO $event): void;
}
