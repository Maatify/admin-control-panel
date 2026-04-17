<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Service;

use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\Contract\ImageProfileValidationServiceInterface;
use Maatify\ImageProfile\DTO\ImageValidationRequestDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;

final class ImageProfileValidationService implements ImageProfileValidationServiceInterface
{
    public function __construct(private readonly ImageProfileQueryReaderInterface $queryReader) {}

    public function validateByCode(string $profileCode, ImageValidationRequestDTO $request): ImageValidationResultDTO
    {
        $profile = $this->queryReader->findByCode($profileCode);
        if ($profile === null) {
            return ImageValidationResultDTO::failed($profileCode, 'Profile not found.');
        }

        if (!$profile->isActive) {
            return ImageValidationResultDTO::failed($profileCode, 'Profile is inactive.');
        }

        if ($profile->minWidth !== null && $request->width < $profile->minWidth) {
            return ImageValidationResultDTO::failed($profileCode, 'Image width is below profile minimum.');
        }

        if ($profile->minHeight !== null && $request->height < $profile->minHeight) {
            return ImageValidationResultDTO::failed($profileCode, 'Image height is below profile minimum.');
        }

        if ($profile->maxWidth !== null && $request->width > $profile->maxWidth) {
            return ImageValidationResultDTO::failed($profileCode, 'Image width exceeds profile maximum.');
        }

        if ($profile->maxHeight !== null && $request->height > $profile->maxHeight) {
            return ImageValidationResultDTO::failed($profileCode, 'Image height exceeds profile maximum.');
        }

        if ($profile->maxSizeBytes !== null && $request->sizeBytes > $profile->maxSizeBytes) {
            return ImageValidationResultDTO::failed($profileCode, 'Image file size exceeds profile maximum.');
        }

        if ($profile->allowedExtensions !== null && $request->extension !== null) {
            $allowed = $this->splitList($profile->allowedExtensions);
            if (!in_array(strtolower($request->extension), $allowed, true)) {
                return ImageValidationResultDTO::failed($profileCode, 'File extension is not allowed by profile.');
            }
        }

        if ($profile->allowedMimeTypes !== null && $request->mimeType !== null) {
            $allowed = $this->splitList($profile->allowedMimeTypes);
            if (!in_array(strtolower($request->mimeType), $allowed, true)) {
                return ImageValidationResultDTO::failed($profileCode, 'MIME type is not allowed by profile.');
            }
        }

        if ($profile->requiresTransparency && !$request->hasTransparency) {
            return ImageValidationResultDTO::failed($profileCode, 'Profile requires image transparency.');
        }

        $ratio = $request->height > 0 ? ($request->width / $request->height) : 0.0;

        if ($profile->minAspectRatio !== null && $ratio < (float) $profile->minAspectRatio) {
            return ImageValidationResultDTO::failed($profileCode, 'Image aspect ratio is below profile minimum.');
        }

        if ($profile->maxAspectRatio !== null && $ratio > (float) $profile->maxAspectRatio) {
            return ImageValidationResultDTO::failed($profileCode, 'Image aspect ratio exceeds profile maximum.');
        }

        return ImageValidationResultDTO::success($profileCode);
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        $normalized = str_replace([';', '|'], ',', strtolower($value));
        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $normalized));

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
    }
}
