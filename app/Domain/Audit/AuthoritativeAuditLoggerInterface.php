<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-17 00:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\DTO\AuditEventDTO;

/**
 * Contract for authoritative audit logging.
 *
 * This interface represents the ONLY allowed write-side
 * for authority and security audit events.
 */
interface AuthoritativeAuditLoggerInterface
{
    /**
     * Persist an authoritative audit event.
     *
     * Implementations MUST be fail-closed once enabled.
     */
    public function log(AuditEventDTO $event): void;
}
