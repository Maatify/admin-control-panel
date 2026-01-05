<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum Scope: string
{
    case LOGIN = 'login';
    case SECURITY = 'security';
    case ROLES_ASSIGN = 'roles.assign';
    case AUDIT_READ = 'audit.read';
    case EXPORT_DATA = 'export.data';
    case SYSTEM_SETTINGS = 'system.settings';
}
