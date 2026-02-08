<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\TranslationValue;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;

final class TranslationValueDeleteSchema implements SchemaInterface
{
    public function validate(array $input): ValidationResultDTO
    {
        $errors = [];

        if (!isset($input['language_id']) || !is_int($input['language_id']) || $input['language_id'] < 1) {
            $errors['language_id'] = [ValidationErrorCodeEnum::REQUIRED_FIELD];
        }

        if (!isset($input['key_id']) || !is_int($input['key_id']) || $input['key_id'] < 1) {
            $errors['key_id'] = [ValidationErrorCodeEnum::REQUIRED_FIELD];
        }

        return new ValidationResultDTO(
            valid: $errors === [],
            errors: $errors
        );
    }
}
