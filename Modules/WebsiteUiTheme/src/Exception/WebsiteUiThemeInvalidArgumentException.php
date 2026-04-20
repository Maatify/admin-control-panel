<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Exception;

use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

final class WebsiteUiThemeInvalidArgumentException extends InvalidArgumentMaatifyException
    implements WebsiteUiThemeExceptionInterface
{
    public static function unexpectedType(string $field, mixed $value): self
    {
        return new self(sprintf('Field "%s" has unexpected type %s.', $field, get_debug_type($value)));
    }
}
