<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Validation\Rules;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

/**
 * Transport-Level Credential Safety Rule.
 *
 * PURPOSE:
 * This rule enforces minimal "Transport Safety" constraints (sanitization)
 * to prevent injection of control characters or malformed strings during login.
 *
 * CRITICAL SECURITY NOTE:
 * This rule strictly DOES NOT enforce password complexity, length, or history policies.
 * It is designed to be backwards-compatible with legacy passwords.
 *
 * USAGE:
 * - Allowed: AuthLoginSchema (Input Validation)
 * - Forbidden: AdminCreateSchema, PasswordChangeSchema (Policy Enforcement)
 */
final class CredentialInputRule
{
    /**
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
