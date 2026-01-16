<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\ActivityLog\Action;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;

enum AdminActivityAction: string implements ActivityActionInterface
{
    // 🔐 Authentication
    case LOGIN_SUCCESS = 'admin.auth.login.success';
    case LOGOUT        = 'admin.auth.logout';

    // 👤 Admin management
    case ADMIN_CREATE  = 'admin.management.create';
    case ADMIN_UPDATE  = 'admin.management.update';
    case ADMIN_DELETE  = 'admin.management.delete';
    case TELEMETRY_LIST  = 'admin.telemetry.list';

    // ⚙️ System (admin-triggered)
    case SETTINGS_UPDATE = 'admin.system.settings.update';

    public function toString(): string
    {
        return $this->value;
    }
}
