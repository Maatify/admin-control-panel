<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\Contracts;

use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Exceptions\ActivityLogMappingException;
use App\Modules\ActivityLog\Exceptions\ActivityLogStorageException;

interface ActivityLogWriterInterface
{
    /**
     * Persist an activity log entry.
     *
     * Implementations MUST:
     * - Be side-effect only
     * - NOT throw domain exceptions
     * - NOT perform validation
     *
     * @throws ActivityLogStorageException On persistent storage failure (e.g. DB connection)
     * @throws ActivityLogMappingException On data mapping failure (e.g. JSON encoding)
     */
    public function write(ActivityLogDTO $activity): void;
}
