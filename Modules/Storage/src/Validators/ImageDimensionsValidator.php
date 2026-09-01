<?php

declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exception\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates that the uploaded image meets minimum and maximum dimension constraints.
 */
final class ImageDimensionsValidator implements FileValidator
{
    public function __construct(
        private readonly ImageDimensions $dimensions,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileException If image dimensions are invalid or outside constraints.
     */
    public function validate(UploadedFileInterface $file): void
    {
        $stream = $file->getStream();
        $stream->rewind();

        /** @var array{0: int, 1: int}|false $imageInfo */
        $imageInfo = @getimagesizefromstring($stream->getContents());

        // Reset stream position for downstream consumers
        $stream->rewind();

        if (!$imageInfo) {
            throw InvalidFileException::invalidImage();
        }

        [$width, $height] = $imageInfo;

        if ($width < $this->dimensions->minWidth || $height < $this->dimensions->minHeight) {
            throw InvalidFileException::imageTooSmall(
                $width,
                $height,
                $this->dimensions
            );
        }

        if ($width > $this->dimensions->maxWidth || $height > $this->dimensions->maxHeight) {
            throw InvalidFileException::imageTooLarge(
                $width,
                $height,
                $this->dimensions
            );
        }
    }
}
