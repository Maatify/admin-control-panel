<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Exception;

use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

final class WebsiteUiThemePersistenceException extends SystemMaatifyException
    implements WebsiteUiThemeExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::DATABASE_CONNECTION_FAILED;
    }

    public static function prepareFailed(string $sql): self
    {
        return new self(sprintf('PDO::prepare() failed for query: %s', $sql));
    }
}
