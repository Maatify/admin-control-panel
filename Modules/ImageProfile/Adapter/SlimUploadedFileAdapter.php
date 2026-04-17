<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Adapter;

use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\Exception\InvalidImageInputException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Converts a PSR-7 {@see UploadedFileInterface} into a neutral
 * {@see ImageFileInputDTO} accepted by the core validator.
 *
 * This adapter is intentionally NOT inside `src/` because:
 *   - It depends on PSR-7 (`psr/http-message`) — an external contract.
 *   - The core library must remain free of any HTTP-layer dependency.
 *
 * Requires in your project composer.json:
 *   "psr/http-message": "^1.1 || ^2.0"
 *
 * Works with any PSR-7 implementation:
 *   - Slim 4 (`Slim\Psr7\UploadedFile`)
 *   - Nyholm PSR-7 (`Nyholm\Psr7\UploadedFile`)
 *   - Guzzle PSR-7 (`GuzzleHttp\Psr7\UploadedFile`)
 *   - Any other compliant implementation
 *
 * Upload error handling:
 *   Any UPLOAD_ERR_* code other than UPLOAD_ERR_OK causes an
 *   {@see InvalidImageInputException} to be thrown before the DTO
 *   is constructed. This prevents passing a broken file to the validator.
 */
final class SlimUploadedFileAdapter
{
    /**
     * @throws InvalidImageInputException on upload error or unreadable stream path.
     */
    public static function toInputDTO(UploadedFileInterface $file): ImageFileInputDTO
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw InvalidImageInputException::uploadError(
                $file->getClientFilename() ?? 'unknown',
                $file->getError(),
            );
        }

        $temporaryPath = $file->getStream()->getMetadata('uri');

        if (! is_string($temporaryPath) || trim($temporaryPath) === '') {
            throw InvalidImageInputException::emptyTemporaryPath();
        }

        return new ImageFileInputDTO(
            originalName:   $file->getClientFilename() ?? 'unknown',
            temporaryPath:  $temporaryPath,
            clientMimeType: $file->getClientMediaType(),
            sizeBytes:      $file->getSize() ?? 0,
        );
    }
}
