<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\NotFound;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class NotFoundMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface { return ErrorCategoryEnum::NOT_FOUND; }
    protected function defaultHttpStatus(): int { return 404; }
    protected function defaultIsSafe(): bool { return true; }
}
