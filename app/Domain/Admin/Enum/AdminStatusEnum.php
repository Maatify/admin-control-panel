<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-21 10:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Admin\Enum;

enum AdminStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case DISABLED = 'DISABLED';

    /**
     * Indicates whether the admin is allowed to authenticate.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Indicates whether the admin is operationally blocked.
     */
    public function isBlocked(): bool
    {
        return $this !== self::ACTIVE;
    }
}
