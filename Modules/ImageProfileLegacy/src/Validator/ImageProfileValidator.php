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

namespace Maatify\ImageProfileLegacy\Validator;

use Maatify\ImageProfileLegacy\Contract\ImageMetadataReaderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileValidatorInterface;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageMetadataDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationErrorCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationErrorDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO;
use Maatify\ImageProfileLegacy\Entity\ImageProfileEntity;
use Maatify\ImageProfileLegacy\Exception\ImageMetadataReadException;
use Maatify\ImageProfileLegacy\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfileLegacy\ValueObject\AllowedMimeTypeCollection;

/**
 * Core image-profile validator.
 *
 * Composition:
 *   - loads profiles through {@see ImageProfileProviderInterface}
 *   - extracts metadata through {@see ImageMetadataReaderInterface}
 *   - returns a typed {@see ImageValidationResultDTO} for every call
 *
 * Error collection policy:
 *   - Short-circuit ONLY for the classes of error that make further
 *     checks meaningless: missing/inactive profile, missing/unreadable
 *     file, unreadable metadata.
 *   - For rule failures (mime, extension, dimensions, size) collect
 *     every failing rule so the caller can present them all at once.
 */
final class ImageProfileValidator implements ImageProfileValidatorInterface
{
    public function __construct(
        private readonly ImageProfileProviderInterface $provider,
        private readonly ImageMetadataReaderInterface  $metadataReader,
    ) {
    }

    public function validateByCode(
        string            $profileCode,
        ImageFileInputDTO $input,
    ): ImageValidationResultDTO {
        $profile = $this->provider->findByCode($profileCode);
        if ($profile === null) {
            return ImageValidationResultDTO::invalid(
                profileCode: $profileCode,
                metadata:    null,
                errors:      new ImageValidationErrorCollectionDTO(
                    ImageValidationErrorDTO::profileNotFound($profileCode),
                ),
            );
        }

        if (! $profile->isActive()) {
            return ImageValidationResultDTO::invalid(
                profileCode: $profileCode,
                metadata:    null,
                errors:      new ImageValidationErrorCollectionDTO(
                    ImageValidationErrorDTO::profileInactive($profileCode),
                ),
            );
        }

        $path = $input->temporaryPath;

        if (! file_exists($path)) {
            return ImageValidationResultDTO::invalid(
                profileCode: $profileCode,
                metadata:    null,
                errors:      new ImageValidationErrorCollectionDTO(
                    ImageValidationErrorDTO::fileNotFound($path),
                ),
            );
        }

        if (! is_readable($path)) {
            return ImageValidationResultDTO::invalid(
                profileCode: $profileCode,
                metadata:    null,
                errors:      new ImageValidationErrorCollectionDTO(
                    ImageValidationErrorDTO::fileNotReadable($path),
                ),
            );
        }

        try {
            $metadata = $this->metadataReader->read($input);
        } catch (ImageMetadataReadException $e) {
            return ImageValidationResultDTO::invalid(
                profileCode: $profileCode,
                metadata:    null,
                errors:      new ImageValidationErrorCollectionDTO(
                    ImageValidationErrorDTO::metadataUnreadable($e->getMessage()),
                ),
            );
        }

        $errors = $this->collectRuleErrors($profile, $metadata);

        if ($errors->isEmpty()) {
            return ImageValidationResultDTO::valid(
                profileCode: $profileCode,
                metadata:    $metadata,
            );
        }

        return ImageValidationResultDTO::invalid(
            profileCode: $profileCode,
            metadata:    $metadata,
            errors:      $errors,
        );
    }

    private function collectRuleErrors(
        ImageProfileEntity     $profile,
        ImageMetadataDTO $metadata,
    ): ImageValidationErrorCollectionDTO {
        $errors = ImageValidationErrorCollectionDTO::empty();

        if (
            $profile->hasMimeTypeRestriction()
            && ! $profile->allowedMimeTypes->has($metadata->detectedMimeType)
        ) {
            $errors = $errors->with(
                ImageValidationErrorDTO::mimeNotAllowed(
                    detectedMime: $metadata->detectedMimeType,
                    allowedList:  $this->joinMimeTypes($profile->allowedMimeTypes),
                ),
            );
        }

        if (
            $profile->hasExtensionRestriction()
            && ! $profile->allowedExtensions->has($metadata->detectedExtension)
        ) {
            $errors = $errors->with(
                ImageValidationErrorDTO::extensionNotAllowed(
                    detectedExt: $metadata->detectedExtension,
                    allowedList: $this->joinExtensions($profile->allowedExtensions),
                ),
            );
        }

        if ($profile->minWidth !== null && $metadata->width < $profile->minWidth) {
            $errors = $errors->with(
                ImageValidationErrorDTO::widthTooSmall($profile->minWidth, $metadata->width),
            );
        }

        if ($profile->minHeight !== null && $metadata->height < $profile->minHeight) {
            $errors = $errors->with(
                ImageValidationErrorDTO::heightTooSmall($profile->minHeight, $metadata->height),
            );
        }

        if ($profile->maxWidth !== null && $metadata->width > $profile->maxWidth) {
            $errors = $errors->with(
                ImageValidationErrorDTO::widthTooLarge($profile->maxWidth, $metadata->width),
            );
        }

        if ($profile->maxHeight !== null && $metadata->height > $profile->maxHeight) {
            $errors = $errors->with(
                ImageValidationErrorDTO::heightTooLarge($profile->maxHeight, $metadata->height),
            );
        }

        if ($profile->maxSizeBytes !== null && $metadata->sizeBytes > $profile->maxSizeBytes) {
            $errors = $errors->with(
                ImageValidationErrorDTO::fileTooLarge($profile->maxSizeBytes, $metadata->sizeBytes),
            );
        }

        // ---- Phase 9: aspect ratio -------------------------------------------

        if ($profile->minAspectRatio !== null || $profile->maxAspectRatio !== null) {
            $ratio = $metadata->height > 0
                ? $metadata->width / $metadata->height
                : 0.0;

            if ($profile->minAspectRatio !== null && $ratio < $profile->minAspectRatio) {
                $errors = $errors->with(
                    ImageValidationErrorDTO::aspectRatioTooNarrow($ratio, $profile->minAspectRatio),
                );
            }

            if ($profile->maxAspectRatio !== null && $ratio > $profile->maxAspectRatio) {
                $errors = $errors->with(
                    ImageValidationErrorDTO::aspectRatioTooWide($ratio, $profile->maxAspectRatio),
                );
            }
        }

        // ---- Phase 9: transparency required -----------------------------------

        if ($profile->requiresTransparency) {
            $alphaCapable = ['image/png', 'image/webp'];
            if (! in_array($metadata->detectedMimeType, $alphaCapable, true)) {
                $errors = $errors->with(
                    ImageValidationErrorDTO::transparencyRequired($metadata->detectedMimeType),
                );
            }
        }

        return $errors;
    }

    private function joinMimeTypes(AllowedMimeTypeCollection $collection): string
    {
        $parts = [];
        foreach ($collection as $value) {
            $parts[] = $value;
        }
        return implode(', ', $parts);
    }

    private function joinExtensions(AllowedExtensionCollection $collection): string
    {
        $parts = [];
        foreach ($collection as $value) {
            $parts[] = $value;
        }
        return implode(', ', $parts);
    }
}
