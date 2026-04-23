<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Maatify\Validation\Rules\Primitive\StringRule;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class TextareaRule
{
    public static function required(int $min = 1, int $max = 5000): Validatable
    {
        return v::allOf(
            StringRule::required($min, $max),
            v::notBlank()
        );
    }

    public static function optional(int $min = 1, int $max = 5000): Validatable
    {
        return v::optional(self::required($min, $max));
    }
}
