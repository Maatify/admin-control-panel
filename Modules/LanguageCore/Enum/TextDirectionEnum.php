<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Enum;

/**
 * Text direction for language rendering (UI-level concern).
 * Used by LanguageSettingsDTO and related services.
 */
enum TextDirectionEnum: string
{
    case LTR = 'ltr';
    case RTL = 'rtl';
}
