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

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

/**
 * Typed outcome of a validation call.
 *
 * Contract:
 *   - `isValid === true`  => `errors->isEmpty() === true`
 *   - `isValid === false` => `errors->isEmpty() === false`
 *
 * `metadata` is nullable because a run may fail before metadata could be
 * read (profile not found, file missing, unreadable file, ...).
 */
final readonly class ImageValidationResultDTO implements JsonSerializable
{
    public function __construct(
        public bool                              $isValid,
        public string                            $profileCode,
        public ?ImageMetadataDTO                 $metadata,
        public ImageValidationErrorCollectionDTO $errors,
        public ImageValidationWarningCollectionDTO $warnings,
    ) {
    }

    public static function valid(
        string $profileCode,
        ImageMetadataDTO $metadata,
        ?ImageValidationWarningCollectionDTO $warnings = null,
    ): self {
        return new self(
            isValid:     true,
            profileCode: $profileCode,
            metadata:    $metadata,
            errors:      ImageValidationErrorCollectionDTO::empty(),
            warnings:    $warnings ?? ImageValidationWarningCollectionDTO::empty(),
        );
    }

    public static function invalid(
        string $profileCode,
        ?ImageMetadataDTO $metadata,
        ImageValidationErrorCollectionDTO $errors,
        ?ImageValidationWarningCollectionDTO $warnings = null,
    ): self {
        return new self(
            isValid:     false,
            profileCode: $profileCode,
            metadata:    $metadata,
            errors:      $errors,
            warnings:    $warnings ?? ImageValidationWarningCollectionDTO::empty(),
        );
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return array{
     *     isValid: bool,
     *     profileCode: string,
     *     metadata: ?ImageMetadataDTO,
     *     errors: ImageValidationErrorCollectionDTO,
     *     warnings: ImageValidationWarningCollectionDTO
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'isValid'     => $this->isValid,
            'profileCode' => $this->profileCode,
            'metadata'    => $this->metadata,
            'errors'      => $this->errors,
            'warnings'    => $this->warnings,
        ];
    }
}
