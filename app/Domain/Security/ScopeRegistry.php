<?php

declare(strict_types=1);

namespace App\Domain\Security;

use App\Domain\Enum\Scope;

class ScopeRegistry
{
    /**
     * @var array<string, Scope>
     */
    private static array $map = [
        'security' => Scope::SECURITY,
        'role.assign' => Scope::ROLES_ASSIGN,
        'admin.preferences.write' => Scope::SYSTEM_SETTINGS,
        'email.verify' => Scope::SECURITY,
        'audit.read' => Scope::AUDIT_READ,

        // Dedicated scope to ensure isolated Step-Up (no reuse of prior SECURITY grants)
        'admin.create' => Scope::ADMIN_CREATE,
        'admin.update' => Scope::ADMIN_UPDATE,
        'admin.email.add' => Scope::ADMIN_EMAIL_ADD,
    ];

    public static function getScopeForRoute(string $routeName): ?Scope
    {
        return self::$map[$routeName] ?? null;
    }
}
