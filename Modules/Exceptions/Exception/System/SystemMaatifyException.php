<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\System;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class SystemMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface { return ErrorCategoryEnum::SYSTEM; }
    protected function defaultHttpStatus(): int { return 500; }
    protected function defaultIsSafe(): bool { return false; }
}
