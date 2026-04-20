<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class WebsiteUiThemeIdentifierRule
{
    public static function entityType(): Validatable
    {
        return v::stringType()->notEmpty()->length(1, 50);
    }

    public static function themeFile(): Validatable
    {
        return v::stringType()->notEmpty()->length(1, 255);
    }
}
