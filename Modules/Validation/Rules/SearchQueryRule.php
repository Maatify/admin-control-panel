<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 01:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class SearchQueryRule
{
    public static function rule(): Validatable
    {
        return v::arrayType()->anyOf(
            // Structure-only validation:
            // search must contain either `global` or non-empty `columns`
            v::key('global', v::stringType()->length(1, 255)),
            v::key('columns', v::arrayType()->notEmpty())
        );
    }
}
