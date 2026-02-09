<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator;

final class SharedStringRequiredSchema extends AbstractSchema
{
    public function __construct(
        private readonly string $field,
        private readonly int $minLength = 1,
        private readonly int $maxLength = 255
    ) {
    }

    /**
     * @return array<string, array{
     *     0: \Respect\Validation\Validatable,
     *     1: \Maatify\Validation\Enum\ValidationErrorCodeEnum
     * }>
     */
    protected function rules(): array
    {
        return [
            $this->field => [
                Validator::stringType()
                    ->notEmpty()
                    ->length($this->minLength, $this->maxLength),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
        ];
    }
}

