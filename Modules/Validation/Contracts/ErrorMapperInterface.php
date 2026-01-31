<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:24
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Contracts;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;

interface ErrorMapperInterface
{
    /**
     * @param array<string, list<ValidationErrorCodeEnum>> $errors
     *
     * @return array<string, mixed>
     */
    public function map(array $errors): array;
}
