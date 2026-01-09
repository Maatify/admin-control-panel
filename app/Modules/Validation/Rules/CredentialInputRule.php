<?php

declare(strict_types=1);

namespace App\Modules\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

final class CredentialInputRule
{
    /**
     * Transport Safety Rule
     * Enforces that the input is a safe string for transmission,
     * but DOES NOT enforce complexity or policy.
     *
     * @return Validatable
     */
    public static function rule(): Validatable
    {
        return v::stringType()
            ->notEmpty()
            ->noWhitespace()
            ->not(v::contains('='))
            ->regex('/^[^\p{C}]*$/u'); // No control characters
    }
}
