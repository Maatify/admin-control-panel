<?php

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
