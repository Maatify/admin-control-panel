<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules;

final class StringPatternRule
{
    public const ROLE_NAME = '/^[a-z][a-z0-9_.-]*$/';

    public const I18N_CODE = '/^[a-z0-9]+([._-][a-z0-9]+)*$/';

    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const PRICE_PATTERN = '/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/';

    public const CLEAN_STRING_PATTERN = '/^[^\p{C}]*$/u';
}
