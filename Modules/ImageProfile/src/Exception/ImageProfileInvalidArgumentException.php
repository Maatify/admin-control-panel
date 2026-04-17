<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Exception;

use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

final class ImageProfileInvalidArgumentException extends InvalidArgumentMaatifyException
    implements ImageProfileExceptionInterface
{
    public static function unexpectedType(string $field, mixed $value): self
    {
        return new self(sprintf('Field "%s" has unexpected type %s.', $field, get_debug_type($value)));
    }

    public static function invalidRule(string $message): self
    {
        return new self($message);
    }
}
