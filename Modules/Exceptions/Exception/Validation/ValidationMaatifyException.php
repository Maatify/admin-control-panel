<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Validation;

use Maatify\Exceptions\Exception\MaatifyException;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;

abstract class ValidationMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::VALIDATION;
    }

    protected function defaultHttpStatus(): int
    {
        return 400;
    }

    protected function defaultIsSafe(): bool
    {
        return true;
    }
}
