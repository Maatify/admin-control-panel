<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class DateRangeRule
{
    public static function required(): Validatable
    {
        return v::arrayType()->keySet(
            v::key('from', v::date('Y-m-d'), false),
            v::key('to', v::date('Y-m-d'), false)
        );
    }

    public static function optional(): Validatable
    {
        return v::optional(self::required());
    }
}
