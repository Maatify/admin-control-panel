<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\Validation;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageTranslationValueDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
