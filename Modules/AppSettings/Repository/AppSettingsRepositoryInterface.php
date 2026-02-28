<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\Repository;

use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;

/**
 * Interface: AppSettingsRepositoryInterface
 *
 * Storage contract for application settings.
 *
 * Responsibilities:
 * - Data persistence ONLY
 * - No validation
 * - No business rules
 * - No cache
 *
 * IMPORTANT:
 * - Implementations MUST respect is_active semantics
 * - No physical delete is allowed (soft disable only)
 */
interface AppSettingsRepositoryInterface
{
    /**
     * Find a single setting value by group + key.
     *
     * @param string $group
     * @param string $key
     * @param bool $onlyActive Whether to restrict lookup to active settings only
     *
     * @return array<string, mixed>|null
     *         Expected keys:
     *         - id (int)
     *         - setting_group (string)
     *         - setting_key (string)
     *         - setting_value (string)
     *         - setting_type (string)
     *         - is_active (bool|int)
     */
    public function findOne(string $group, string $key, bool $onlyActive = true): ?array;

    /**
     * Retrieve all active settings for a group.
     *
     * @param string $group
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllByGroup(string $group): array;

    /**
     * Check if a setting exists.
     *
     * @param string $group
     * @param string $key
     * @param bool $onlyActive
     *
     * @return bool
     */
    public function exists(string $group, string $key, bool $onlyActive = false): bool;

    /**
     * Insert a new application setting.
     *
     * @param AppSettingDTO $dto
     *
     * @return int Inserted record ID
     */
    public function insert(AppSettingDTO $dto): int;

    /**
     * Update the value (and optionally value type) of an existing setting.
     *
     * @param AppSettingUpdateDTO $dto
     *
     * @return void
     */
    public function updateValue(AppSettingUpdateDTO $dto): void;

    /**
     * Enable or disable a setting using soft activation.
     *
     * @param AppSettingKeyDTO $key
     * @param bool $isActive
     *
     * @return void
     */
    public function setActiveStatus(AppSettingKeyDTO $key, bool $isActive): void;

    /**
     * Query settings for listing/searching (admin usage).
     *
     * @param AppSettingsQueryDTO $query
     *
     * @return array<int, array<string, mixed>>
     */
    public function query(AppSettingsQueryDTO $query): array;
}
