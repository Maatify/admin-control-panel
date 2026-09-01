<?php

declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exception\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates that the uploaded file does not exceed a maximum size.
 */
final class SizeValidator implements FileValidator
{
    public function __construct(
        private readonly int $maxSizeBytes,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileException If file size exceeds the limit.
     */
    public function validate(UploadedFileInterface $file): void
    {
        $fileSize = $file->getSize();

        if ($fileSize === null || $fileSize > $this->maxSizeBytes) {
            throw InvalidFileException::fileTooLarge(
                $fileSize ?? 0,
                $this->maxSizeBytes
            );
        }
    }
}
