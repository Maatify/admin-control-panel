<?php

declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exception\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates that the uploaded file has an allowed extension.
 */
final class ExtensionValidator implements FileValidator
{
    /**
     * @param array<string> $allowedExtensions e.g. ['jpg', 'jpeg', 'png', 'webp']
     */
    public function __construct(
        private readonly array $allowedExtensions,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileException If extension is not in the allowed list.
     */
    public function validate(UploadedFileInterface $file): void
    {
        $extension = strtolower(pathinfo(
            $file->getClientFilename() ?? '',
            PATHINFO_EXTENSION
        ));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw InvalidFileException::unsupportedExtension(
                $extension,
                $this->allowedExtensions
            );
        }
    }
}
