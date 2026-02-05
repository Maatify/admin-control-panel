<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\Policy;

use Maatify\AppSettings\Exception\AppSettingProtectedException;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;

/**
 * Class: AppSettingsProtectionPolicy
 *
 * Protects critical application settings from being
 * disabled or modified in dangerous ways.
 *
 * IMPORTANT:
 * - Protected settings MUST always remain active
 * - Attempts to deactivate or modify them must fail loudly
 */
final class AppSettingsProtectionPolicy
{
    /**
     * List of protected (group.key) identifiers.
     *
     * @var array<int, string>
     */
    private const PROTECTED = [
        'system.base_url',
        'system.environment',
        'system.timezone',
    ];

    /**
     * Assert that a setting is NOT protected.
     *
     * @throws AppSettingProtectedException
     */
    public static function assertNotProtected(AppSettingKeyDTO $key): void
    {
        $identifier = self::normalize($key->group) . '.' . self::normalize($key->key);

        if (in_array($identifier, self::PROTECTED, true)) {
            throw new AppSettingProtectedException(
                sprintf('Setting "%s" is protected and cannot be modified', $identifier)
            );
        }
    }

    /**
     * Normalize input to a canonical format.
     */
    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    public static function isProtected(string $group, string $key): bool
    {
        $identifier = self::normalize($group) . '.' . self::normalize($key);
        return in_array($identifier, self::PROTECTED, true);
    }
}
