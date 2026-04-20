<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\PositiveEntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageClearFallbackSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                PositiveEntityIdRule::rule(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
