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

namespace Maatify\AdminKernel\Domain\Admin\Enum;

enum AdminActivityActionEnum: string
{
    // 🔐 Authentication
    case LOGIN_SUCCESS = 'admin.auth.login.success';
    case LOGOUT        = 'admin.auth.logout';

    // 👤 Admin management
    case ADMIN_CREATE  = 'admin.management.create';
    case ADMIN_UPDATE  = 'admin.management.update';
    case ADMIN_DELETE  = 'admin.management.delete';
    case ADMIN_EMAIL_ADDED = 'admin.management.email.add';
    case ADMIN_EMAIL_REPLACED = 'admin.management.email.replace';
    const ADMIN_EMAIL_VERIFIED               = 'admin.management.email.verify';
    const ADMIN_EMAIL_VERIFICATION_RESTARTED = 'admin.management.email.verification.restarted';
    const ADMIN_EMAIL_FAILED                 = 'admin.management.email.failed';

    case TELEMETRY_LIST  = 'admin.telemetry.list';

    // ───────────────
    // Sessions
    // ───────────────
    case SESSION_REVOKE = 'admin.session.revoked';

    case SESSION_BULK_REVOKE = 'admin.session.bulk_revoked';


    // ⚙️ System (admin-triggered)
    case SETTINGS_UPDATE = 'admin.system.settings.update';

    case ADMIN_NOTIFICATION_PREFERENCE_UPDATED = 'admin.notification.preference.updated';
    case ADMIN_NOTIFICATION_MARK_READ = 'admin.notification.mark.read';

    case ROLE_ASSIGN = 'admin.role.assign';
    case ROLE_ASSIGN_DENIED = 'admin.role.assign.denied';


    public function toString(): string
    {
        return $this->value;
    }
}
