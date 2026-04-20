<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-20 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class RoleNameRule
{
    public static function rule(int $min = 3, int $max = 190): Validatable
    {
        return v::stringType()
            ->notEmpty()
            ->length($min, $max)
            ->regex('/^[a-z][a-z0-9_.-]*$/');
    }
}
