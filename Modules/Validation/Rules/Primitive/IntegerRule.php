<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class IntegerRule
{
    public static function required(?int $min = null, ?int $max = null): Validatable
    {
        $rule = v::intVal();

        if ($min !== null) {
            $rule = $rule->min($min);
        }

        if ($max !== null) {
            $rule = $rule->max($max);
        }

        return $rule;
    }

    public static function optional(?int $min = null, ?int $max = null): Validatable
    {
        return v::optional(self::required($min, $max));
    }
}
