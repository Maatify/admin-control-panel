<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings;

use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;

/**
 * Interface: AppSettingsServiceInterface
 *
 * Public contract for interacting with application settings.
 *
 * This interface is the ONLY entry point for:
 * - Admin panels
 * - Web applications
 * - Mobile apps
 *
 * No consumer should ever call repositories directly.
 */
interface AppSettingsServiceInterface
{
    /**
     * Get a single active setting value.
     */
    public function get(string $group, string $key): string;

    /**
     * Get a single active setting value, cast to its declared type.
     */
    public function getTyped(string $group, string $key): mixed;

    /**
     * Check if an active setting exists.
     */
    public function has(string $group, string $key): bool;

    /**
     * Get all active settings within a group.
     *
     * @return array<string, string> key => value
     */
    public function getGroup(string $group): array;

    /**
     * Create a new application setting.
     */
    public function create(AppSettingDTO $dto): void;

    /**
     * Update an existing application setting value.
     */
    public function update(AppSettingUpdateDTO $dto): void;

    /**
     * Enable or disable a setting (soft activation).
     */
    public function setActive(AppSettingKeyDTO $key, bool $isActive): void;

    /**
     * Query settings (admin usage).
     *
     * @return array<int, array<string, mixed>>
     */
    public function query(AppSettingsQueryDTO $query): array;
}
