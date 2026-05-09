<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Maatify\Validation\Rules\Primitive\StringRule;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class WebsiteUiThemeIdentifierRule
{
    public static function entityType(): Validatable
    {
        return v::allOf(
            StringRule::required(1, 50),
            v::notEmpty()
        );
    }

    public static function themeFile(): Validatable
    {
        return v::allOf(
            StringRule::required(1, 255),
            v::notEmpty()
        );
    }
}
