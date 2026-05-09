<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class ArrayRule
{
    public static function required(): Validatable
    {
        return v::arrayType();
    }

    public static function optional(): Validatable
    {
        return v::optional(self::required());
    }

    public static function requiredNotEmpty(): Validatable
    {
        return v::arrayType()->notEmpty();
    }

    public static function optionalNotEmpty(): Validatable
    {
        return v::optional(self::requiredNotEmpty());
    }
}
