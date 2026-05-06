<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class StrictBooleanRule
{
    public static function required(): Validatable
    {
        return v::boolType();
    }

    public static function optional(): Validatable
    {
        return v::optional(self::required());
    }
}
