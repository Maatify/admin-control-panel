<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\CredentialInputRule;
use Maatify\Validation\Rules\Primitive\EmailRule;

/**
 * Authentication Input Schema
 *
 * NOTE: Authentication validates transport safety only.
 * Password policy applies exclusively to creation and mutation flows.
 */
class AuthLoginSchemaExample extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'email' => [EmailRule::required(), ValidationErrorCodeEnum::INVALID_EMAIL],
            'password' => [CredentialInputRule::rule(), ValidationErrorCodeEnum::INVALID_PASSWORD],
        ];
    }
}
