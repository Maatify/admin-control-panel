<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 10:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

/**
 * I18n scope_code, domain_code, Key Naming Rule
 *
 * Enforces canonical i18n key format:
 *
 * - lowercase letters
 * - numbers
 * - dot / underscore / dash separators
 * - no leading/trailing separators
 * - no consecutive separators
 *
 * Examples:
 *  ✔ home.title
 *  ✔ user_profile.name
 *  ✔ auth-login.button
 *
 *  ✘ .home
 *  ✘ home.
 *  ✘ home..title
 */
final class I18nCodeRule
{
    public static function rule(int $min, int $max): Validatable
    {
        return v::stringType()
            ->notEmpty()
            ->length($min, $max)
            ->regex('/^[a-z0-9]+([._-][a-z0-9]+)*$/');
    }
}
