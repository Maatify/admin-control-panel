<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class RoleNameRule
{
    public static function rule(int $min = 3, int $max = 190): Validatable
    {
        return v::stringType()
            ->notEmpty()
            ->length($min, $max)
            ->regex('/^[a-z][a-z0-9_.-]*$/');
    }
}
