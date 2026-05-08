<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StringRule;

final class WebsiteUiThemeIdentifierRule
{
    public static function entityType(): Validatable
    {
        return StringRule::required(1, 50);
    }

    public static function themeFile(): Validatable
    {
        return StringRule::required(1, 255);
    }
}
