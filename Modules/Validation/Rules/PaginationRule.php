<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 01:59
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class PaginationRule
{
    public static function page(): Validatable
    {
        return v::optional(
            v::intVal()->min(1)
        );
    }

    public static function perPage(int $max = 100): Validatable
    {
        return v::optional(
            v::intVal()->min(1)->max($max)
        );
    }
}
