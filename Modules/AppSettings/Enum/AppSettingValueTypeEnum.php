<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:26
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\Enum;

/**
 * Enum: AppSettingValueTypeEnum
 *
 * Represents the logical type of an application setting value.
 *
 * NOTE:
 * - Casting / parsing decisions are handled by the service layer
 */
enum AppSettingValueTypeEnum: string
{
    case STRING = 'string';
    case INT = 'int';
    case BOOL = 'bool';
    case JSON = 'json';
}
