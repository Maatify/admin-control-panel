<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Contracts;

use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use App\Modules\SecurityEvents\Exceptions\SecurityEventStorageException;

/**
 * Contract for recording security-related events.
 *
 * Implementations are honest and MAY throw exceptions on failure.
 * The best-effort policy MUST be enforced by the Domain Recorder,
 * not by this module contract.
 *
 * This interface is designed to be implemented by infrastructure
 * adapters (e.g. PDO, Queue, External SIEM).
 */
interface SecurityEventLoggerInterface
{
    /**
     * Record a security event.
     *
     * @param   SecurityEventDTO  $event
     * @throws  SecurityEventStorageException If storage fails.
     */
    public function log(SecurityEventDTO $event): void;
}
