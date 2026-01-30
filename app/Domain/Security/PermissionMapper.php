<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-28 12:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Security;

use App\Domain\Contracts\PermissionMapperInterface;

final class PermissionMapper implements PermissionMapperInterface
{
    private const MAP = [
        // Admins
        'admins.list.ui'  => 'admins.list',
        'admins.list.api' => 'admins.list',

        // Admin Profile
        'admins.profile.edit.view' => 'admins.profile.edit',
        'admins.profile.edit'      => 'admins.profile.edit',

        'admin.create.ui'  => 'admin.create',
        'admin.create.api' => 'admin.create',

        // Sessions
        'sessions.list.ui'  => 'sessions.list',
        'sessions.list.api' => 'sessions.list',

        'sessions.revoke.id'   => 'sessions.revoke',
        'sessions.revoke.bulk' => 'sessions.revoke',
    ];

    public function map(string $routeName): string
    {
        return self::MAP[$routeName] ?? $routeName;
    }
}
