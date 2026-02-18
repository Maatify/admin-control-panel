<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Exceptions;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;

final class ValidationFailedException extends MaatifyException
{
    public function __construct(
        private readonly ValidationResultDTO $result
    ) {
        parent::__construct(
            message: 'Validation failed.',
            meta: ['errors' => $result->getErrors()]
        );
    }

    /**
     * @return array<string, list<ValidationErrorCodeEnum>>
     */
    public function getErrors(): array
    {
        return $this->result->getErrors();
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::VALIDATION_FAILED;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::VALIDATION;
    }

    protected function defaultHttpStatus(): int
    {
        return 422;
    }

    protected function defaultIsSafe(): bool
    {
        return true;
    }

    protected function defaultIsRetryable(): bool
    {
        return false;
    }
}
