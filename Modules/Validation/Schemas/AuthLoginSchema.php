<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\CredentialInputRule;
use Respect\Validation\Validator as v;

/**
 * Authentication Input Schema
 *
 * NOTE: Authentication validates transport safety only.
 * Password policy applies exclusively to creation and mutation flows.
 */
class AuthLoginSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'email' => [v::email(), ValidationErrorCodeEnum::INVALID_EMAIL],
            'password' => [CredentialInputRule::rule(), ValidationErrorCodeEnum::INVALID_PASSWORD],
        ];
    }
}
