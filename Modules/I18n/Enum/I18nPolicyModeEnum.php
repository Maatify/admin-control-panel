<?php

declare(strict_types=1);

namespace Maatify\I18n\Enum;

enum I18nPolicyModeEnum: string
{
    case STRICT = 'strict';
    case PERMISSIVE = 'permissive';
}
