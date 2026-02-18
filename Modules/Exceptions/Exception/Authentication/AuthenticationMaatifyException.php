<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Authentication;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class AuthenticationMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface { return ErrorCategoryEnum::AUTHENTICATION; }
    protected function defaultHttpStatus(): int { return 401; }
    protected function defaultIsSafe(): bool { return true; }
}
