<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;

/**
 * Neutral representation of an image file handed to the package for
 * validation.
 *
 * This DTO is framework-agnostic. Adapters for Slim / Symfony / Laravel /
 * native `$_FILES` live OUTSIDE the core package and are responsible for
 * producing instances of this type.
 */
final readonly class ImageFileInputDTO implements JsonSerializable
{
    /**
     * @throws InvalidImageInputException
     */
    public function __construct(
        public string  $originalName,
        public string  $temporaryPath,
        public ?string $clientMimeType,
        public int     $sizeBytes,
    ) {
        if (trim($this->originalName) === '') {
            throw InvalidImageInputException::emptyOriginalName();
        }
        if (trim($this->temporaryPath) === '') {
            throw InvalidImageInputException::emptyTemporaryPath();
        }
        if ($this->sizeBytes < 0) {
            throw InvalidImageInputException::negativeSize($this->sizeBytes);
        }
    }

    /**
     * @return array{
     *     originalName: string,
     *     temporaryPath: string,
     *     clientMimeType: ?string,
     *     sizeBytes: int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'originalName'   => $this->originalName,
            'temporaryPath'  => $this->temporaryPath,
            'clientMimeType' => $this->clientMimeType,
            'sizeBytes'      => $this->sizeBytes,
        ];
    }
}
