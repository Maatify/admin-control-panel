<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use App\Modules\Validation\Rules\CredentialInputRule;
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
