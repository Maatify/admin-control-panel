<?php

declare(strict_types=1);

namespace Maatify\Validation\Rules\Semantic;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class WysiwygContentRule
{
    public static function required(int $min = 1, int $max = 65535): Validatable
    {
        return v::allOf(
            v::stringType()->length($min, $max),
            v::callback([self::class, 'isVisuallyNotEmpty'])
        );
    }

    public static function optional(int $min = 1, int $max = 65535): Validatable
    {
        return v::optional(self::required($min, $max));
    }

    public static function isVisuallyNotEmpty(mixed $input): bool
    {
        if (!is_string($input)) {
            return false;
        }

        $normalized = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/<br\s*\/?>/i', '', $normalized);
        $normalized = strip_tags((string) $normalized);
        $normalized = str_replace("\xc2\xa0", ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized !== '';
    }
}
