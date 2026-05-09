<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\StringPatternRule;
use Respect\Validation\Validatable;

final class RoleNameRule
{
    public static function rule(int $min = 3, int $max = 190): Validatable
    {
        return StringRule::required($min, $max, StringPatternRule::ROLE_NAME);
    }
}
