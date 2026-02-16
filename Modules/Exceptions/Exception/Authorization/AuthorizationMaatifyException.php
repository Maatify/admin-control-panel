<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Authorization;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class AuthorizationMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryEnum { return ErrorCategoryEnum::AUTHORIZATION; }
    protected function defaultHttpStatus(): int { return 403; }
    protected function defaultIsSafe(): bool { return true; }
}
