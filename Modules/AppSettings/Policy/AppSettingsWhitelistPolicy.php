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

use Maatify\AppSettings\Exception\InvalidAppSettingException;

/**
 * Class: AppSettingsWhitelistPolicy
 *
 * Defines which setting groups and keys are allowed
 * to exist inside the AppSettings module.
 *
 * Design Notes:
 * - Injectable (DI-friendly)
 * - Pure configuration (no hardcoded defaults)
 * - Host project MUST provide whitelist via container definition
 * - Prevents config drift & typos
 */
final class AppSettingsWhitelistPolicy
{
    /**
     * Normalized allowed map.
     *
     * @var array<string, array<int, string>>
     */
    private array $allowed;

    /**
     * @param array<string, array<int, string>> $allowed
     */
    public function __construct(array $allowed = [])
    {
        $this->allowed = $this->normalizeAllowed($allowed);
    }

    /**
     * Validate that a group and key are allowed.
     *
     * @throws InvalidAppSettingException
     */
    public function assertAllowed(string $group, string $key): void
    {
        $group = self::normalize($group);
        $key = self::normalize($key);

        if (! isset($this->allowed[$group])) {
            throw new InvalidAppSettingException(
                sprintf('Setting group "%s" is not allowed', $group)
            );
        }

        $allowedKeys = $this->allowed[$group];

        if (in_array('*', $allowedKeys, true)) {
            return;
        }

        if (! in_array($key, $allowedKeys, true)) {
            throw new InvalidAppSettingException(
                sprintf('Setting key "%s.%s" is not allowed', $group, $key)
            );
        }
    }

    /**
     * Check if group/key is allowed.
     */
    public function isAllowed(string $group, string $key): bool
    {
        try {
            $this->assertAllowed($group, $key);
            return true;
        } catch (InvalidAppSettingException) {
            return false;
        }
    }

    /**
     * Normalize allowed structure.
     *
     * @param array<string, array<int, string>> $allowed
     * @return array<string, array<int, string>>
     */
    private function normalizeAllowed(array $allowed): array
    {
        $normalized = [];

        foreach ($allowed as $group => $keys) {
            $group = self::normalize($group);

            $normalized[$group] = array_map(
                fn(string $key) => self::normalize($key),
                $keys
            );
        }

        return $normalized;
    }

    /**
     * Normalize string.
     */
    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * @return array<string, array<int,string>>
     */
    public function getAllowed(): array
    {
        return $this->allowed;
    }
}
