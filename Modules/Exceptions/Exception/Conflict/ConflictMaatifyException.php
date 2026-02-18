<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Conflict;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class ConflictMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface { return ErrorCategoryEnum::CONFLICT; }
    protected function defaultHttpStatus(): int { return 409; }
    protected function defaultIsSafe(): bool { return true; }
}
