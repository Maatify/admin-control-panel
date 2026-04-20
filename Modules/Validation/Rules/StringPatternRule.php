<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules;

final class StringPatternRule
{
    public const ROLE_NAME = '/^[a-z][a-z0-9_.-]*$/';

    public const I18N_CODE = '/^[a-z0-9]+([._-][a-z0-9]+)*$/';
}
