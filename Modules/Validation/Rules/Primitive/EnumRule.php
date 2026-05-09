<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Primitive;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class EnumRule
{
    /**
     * @param array<int|string, mixed> $allowedValues
     */
    public static function required(array $allowedValues): Validatable
    {
        return v::in($allowedValues);
    }

    /**
     * @param array<int|string, mixed> $allowedValues
     */
    public static function optional(array $allowedValues): Validatable
    {
        return v::optional(self::required($allowedValues));
    }
}
