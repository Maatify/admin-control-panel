<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Maatify\Validation\Rules\Primitive\StringRule;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class PasswordRule
{
    public static function required(int $min = 8, int $max = 255): Validatable
    {
        return v::allOf(
            StringRule::required($min, $max),
            v::regex('/[A-Z]/'),
            v::regex('/[a-z]/'),
            v::regex('/[0-9]/')
        );
    }

    public static function optional(int $min = 8, int $max = 255): Validatable
    {
        return v::optional(self::required($min, $max));
    }
}
