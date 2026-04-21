<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Auth;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\CredentialInputRule;
use Maatify\Validation\Rules\Primitive\EmailRule;
use Maatify\Validation\Schemas\AbstractSchema;

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
            'email' => [EmailRule::required(), ValidationErrorCodeEnum::INVALID_EMAIL],
            'password' => [CredentialInputRule::rule(), ValidationErrorCodeEnum::INVALID_PASSWORD],
        ];
    }
}
