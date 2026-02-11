<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class I18nDomainUpdateSortSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'position' => [
                v::intVal()->min(0),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}

