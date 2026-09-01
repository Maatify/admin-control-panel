<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

final class ReaderAdapterException extends ReaderException
{
    public static function adapterError(string $message): self
    {
        return new self(sprintf('Reader adapter error: %s', $message));
    }

    public static function fromThrowable(\Throwable $e): self
    {
        return new self(
            sprintf('Reader adapter error: %s', $e->getMessage()),
            previous: $e
        );
    }
}
