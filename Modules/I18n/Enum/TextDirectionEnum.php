<?php

declare(strict_types=1);

namespace Maatify\I18n\Enum;

/**
 * Text direction for language rendering (UI-level concern).
 * Used by LanguageSettingsDTO and related services.
 */
enum TextDirectionEnum: string
{
    case LTR = 'ltr';
    case RTL = 'rtl';
}
