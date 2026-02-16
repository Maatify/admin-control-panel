<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Authentication;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class AuthenticationMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryEnum { return ErrorCategoryEnum::AUTHENTICATION; }
    protected function defaultHttpStatus(): int { return 401; }
    protected function defaultIsSafe(): bool { return true; }
}
