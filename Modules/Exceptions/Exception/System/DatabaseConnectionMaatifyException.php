<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\System;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

final class DatabaseConnectionMaatifyException
    extends SystemMaatifyException
{
    protected function defaultCategory(): ErrorCategoryEnum { return ErrorCategoryEnum::SYSTEM; }

    protected function defaultErrorCode(): ErrorCodeEnum { return ErrorCodeEnum::DATABASE_CONNECTION_FAILED; }
    protected function defaultHttpStatus(): int { return 503; }
    protected function defaultIsSafe(): bool { return false; }
}
