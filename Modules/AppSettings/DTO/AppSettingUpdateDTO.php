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
 * DTO: AppSettingUpdateDTO
 *
 * Used when updating an existing setting value.
 *
 * NOTE:
 * - group + key identify the setting
 * - valueType is optional and may remain unchanged
 */
final readonly class AppSettingUpdateDTO
{
    public function __construct(
        public string $group,
        public string $key,
        public string $value,
        public ?AppSettingValueTypeEnum $valueType = null
    )
    {
    }
}
