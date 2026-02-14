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
 * Design Notes:
 * - Injectable (DI-friendly)
 * - Has secure default protected list
 * - Host project may override via container definition
 * - Backward compatible behavior
 */
final class AppSettingsProtectionPolicy
{
    /**
     * Secure default protected identifiers.
     *
     * @var array<int, string>
     */
    private const DEFAULT_PROTECTED = [
        'system.base_url',
        'system.environment',
        'system.timezone',
    ];

    /**
     * Normalized protected identifiers.
     *
     * @var array<int, string>
     */
    private array $protected;

    /**
     * @param array<int, string>|null $protected
     */
    public function __construct(?array $protected = null)
    {
        $protected ??= self::DEFAULT_PROTECTED;

        $this->protected = array_map(
            fn(string $value) => self::normalize($value),
            $protected
        );
    }

    /**
     * Assert that a setting is NOT protected.
     *
     * @throws AppSettingProtectedException
     */
    public function assertNotProtected(AppSettingKeyDTO $key): void
    {
        if ($this->isProtected($key->group, $key->key)) {
            $identifier = self::normalize($key->group) . '.' . self::normalize($key->key);

            throw new AppSettingProtectedException(
                sprintf('Setting "%s" is protected and cannot be modified', $identifier)
            );
        }
    }

    /**
     * Determine if a setting is protected.
     */
    public function isProtected(string $group, string $key): bool
    {
        $identifier = self::normalize($group) . '.' . self::normalize($key);

        return in_array($identifier, $this->protected, true);
    }

    /**
     * Normalize input to canonical format.
     */
    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
