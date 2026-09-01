<?php

declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Contract for file validation.
 *
 * Implementations must validate the file and throw InvalidFileException on failure.
 * Multiple validators can be chained to enforce different constraints.
 */
interface FileValidator
{
    /**
     * Validate the uploaded file.
     *
     * @param UploadedFileInterface $file The file to validate.
     *
     * @throws \Maatify\Storage\Exception\InvalidFileException If validation fails.
     */
    public function validate(UploadedFileInterface $file): void;
}
