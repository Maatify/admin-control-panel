<?php

declare(strict_types=1);

namespace Maatify\Settings\Exception;

final class SettingsInvalidArgumentException extends \RuntimeException implements SettingsExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self("Field [{$field}] must not be empty.");
    }

    public static function keyNotEditable(string $key): self
    {
        return new self("Setting key [{$key}] is not editable from admin UI.");
    }

    public static function invalidValueType(string $valueType): self
    {
        return new self("Invalid value type [{$valueType}]. Allowed: bool, int, string, datetime, date.");
    }
}
