<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

enum Scope: string
{
    case LOGIN = 'login';
    case SECURITY = 'security';
    case ROLES_ASSIGN = 'roles.assign';
    case AUDIT_READ = 'audit.read';
    case EXPORT_DATA = 'export.data';
    case SYSTEM_SETTINGS = 'system.settings';

    // scoped actions for admins
    case ADMIN_CREATE = 'admin.create';
    case ADMIN_UPDATE = 'admins.profile.edit';
    case ADMIN_EMAIL_ADD = 'admin.email.add';
}
