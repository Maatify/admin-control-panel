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

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class StringRule
{
    public static function required(int $min = 1, int $max = 255, ?string $regex = null): Validatable
    {
        $rule = v::stringType()->length($min, $max);

        if ($regex !== null) {
            $rule = $rule->regex($regex);
        }

        return $rule;
    }

    public static function optional(int $min = 1, int $max = 255, ?string $regex = null): Validatable
    {
        return v::optional(self::required($min, $max, $regex));
    }
}
