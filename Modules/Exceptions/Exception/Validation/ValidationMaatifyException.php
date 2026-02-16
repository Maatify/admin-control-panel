<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Validation;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class ValidationMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryEnum { return ErrorCategoryEnum::VALIDATION; }
    protected function defaultHttpStatus(): int { return 400; }
    protected function defaultIsSafe(): bool { return true; }
}
