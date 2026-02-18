<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\BusinessRule;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class BusinessRuleMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface { return ErrorCategoryEnum::BUSINESS_RULE; }
    protected function defaultHttpStatus(): int { return 422; }
    protected function defaultIsSafe(): bool { return true; }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::BUSINESS_RULE_VIOLATION;
    }
}
