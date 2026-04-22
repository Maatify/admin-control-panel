<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\StringPatternRule;
use Respect\Validation\Validatable;

final class SlugRule
{
    public static function required(
        int $min = 1,
        int $max = 255,
        string $regex = StringPatternRule::SLUG_PATTERN
    ): Validatable {
        return StringRule::required($min, $max, $regex);
    }

    public static function optional(
        int $min = 1,
        int $max = 255,
        string $regex = StringPatternRule::SLUG_PATTERN
    ): Validatable {
        return StringRule::optional($min, $max, $regex);
    }
}
