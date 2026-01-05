<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AuditEventDTO;

/**
 * Authoritative source for security-critical audit events.
 * Implementations MUST enforce an active transaction.
 * Used ONLY for: Security events, Privilege changes, Sessions, Step-Up.
 */
interface AuthoritativeSecurityAuditWriterInterface
{
    public function write(AuditEventDTO $event): void;
}
