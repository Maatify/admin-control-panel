<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

final class ImageProfileNotFoundException extends ResourceNotFoundMaatifyException
    implements ImageProfileExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Image profile with id %d not found.', $id));
    }

    public static function withCode(string $code): self
    {
        return new self(sprintf('Image profile with code "%s" not found.', $code));
    }
}
