<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\TranslationValue\Validation;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\SharedListQuerySchema;

final class TranslationValuesQuerySchema implements SchemaInterface
{
    public function validate(array $input): ValidationResultDTO
    {
        // 1️⃣ Validate list payload canonically
        $listResult = (new SharedListQuerySchema())->validate($input);
        if (! $listResult->isValid()) {
            return $listResult;
        }

        // 2️⃣ Validate language_id only
        if (!array_key_exists('language_id', $input) || !is_int($input['language_id']) || $input['language_id'] < 1) {
            return new ValidationResultDTO(
                valid: false,
                errors: ['language_id' => [ValidationErrorCodeEnum::REQUIRED_FIELD]]
            );
        }

        return new ValidationResultDTO(valid: true, errors: []);
    }
}

