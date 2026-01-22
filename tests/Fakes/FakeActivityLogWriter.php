<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Exceptions\ActivityLogStorageException;

final class FakeActivityLogWriter implements ActivityLogWriterInterface
{
    public ?ActivityLogDTO $lastActivity = null;
    public bool $throwException = false;

    public function write(ActivityLogDTO $activity): void
    {
        if ($this->throwException) {
            throw new ActivityLogStorageException('DB down');
        }

        $this->lastActivity = $activity;
    }
}
