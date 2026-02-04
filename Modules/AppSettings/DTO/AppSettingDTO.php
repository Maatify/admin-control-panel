<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\DTO;

use Maatify\AppSettings\Enum\AppSettingValueTypeEnum;

/**
 * DTO: AppSettingDTO
 *
 * Used when creating a new application setting.
 */
final readonly class AppSettingDTO
{
    public function __construct(
        public string $group,
        public string $key,
        public string $value,
        public AppSettingValueTypeEnum $valueType = AppSettingValueTypeEnum::STRING,
        public bool $isActive = true
    )
    {
    }
}
