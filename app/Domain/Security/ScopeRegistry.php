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
        'admin.create' => Scope::SECURITY, // Creating admin is security critical
        'admin.preferences.write' => Scope::SYSTEM_SETTINGS,
        'email.verify' => Scope::SECURITY,
        'audit.read' => Scope::AUDIT_READ,
    ];

    public static function getScopeForRoute(string $routeName): ?Scope
    {
        return self::$map[$routeName] ?? null;
    }
}
