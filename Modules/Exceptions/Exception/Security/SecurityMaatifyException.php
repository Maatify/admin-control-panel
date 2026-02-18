<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Security;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class SecurityMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::SECURITY;
    }

    protected function defaultHttpStatus(): int
    {
        return 403; // sensible default for security enforcement
    }

    protected function defaultIsSafe(): bool
    {
        return true; // message safe to expose
    }
}
