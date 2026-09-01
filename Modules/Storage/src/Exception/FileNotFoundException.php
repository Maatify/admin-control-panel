<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

final class FileNotFoundException extends ReaderException
{
    public static function notFound(string $path): self
    {
        return new self(sprintf('File not found: %s', $path));
    }
}
