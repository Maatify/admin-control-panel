<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class DateRule
{
    public static function required(string $format = 'Y-m-d'): Validatable
    {
        return v::dateTime($format);
    }

    public static function optional(string $format = 'Y-m-d'): Validatable
    {
        return v::optional(self::required($format));
    }
}
