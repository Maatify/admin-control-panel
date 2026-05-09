<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class DecimalRule
{
    public static function required(int $scale = 2, ?string $min = null, ?string $max = null): Validatable
    {
        $regex = $scale > 0
            ? '/^(0|[1-9]\d*)(\.\d{1,' . $scale . '})?$/'
            : '/^(0|[1-9]\d*)$/';

        $rule = v::stringType()->regex($regex);

        if ($min !== null) {
            $rule = $rule->min($min);
        }

        if ($max !== null) {
            $rule = $rule->max($max);
        }

        return $rule;
    }

    public static function optional(int $scale = 2, ?string $min = null, ?string $max = null): Validatable
    {
        return v::optional(self::required($scale, $min, $max));
    }
}
