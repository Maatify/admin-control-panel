<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class StrictEntityIdRule
{
    public static function required(): Validatable
    {
        return v::intType()->min(1);
    }

    public static function optional(): Validatable
    {
        return v::optional(self::required());
    }
}
