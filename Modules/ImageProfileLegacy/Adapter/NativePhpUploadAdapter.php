<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Adapter;

use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;

/**
 * Converts a single native PHP `$_FILES` entry into a neutral
 * {@see ImageFileInputDTO} accepted by the core validator.
 *
 * This adapter is intentionally NOT inside `src/` because:
 *   - It reads directly from the PHP superglobal `$_FILES`.
 *   - The core library must remain free of any server-API dependency.
 *
 * No external dependencies required — uses only native PHP.
 *
 * Expected input shape (one entry from $_FILES):
 * <code>
 * [
 *     'name'     => 'photo.jpg',
 *     'type'     => 'image/jpeg',   // client-provided — not trusted for validation
 *     'tmp_name' => '/tmp/phpXYZ',
 *     'error'    => UPLOAD_ERR_OK,
 *     'size'     => 204800,
 * ]
 * </code>
 *
 * The `type` field from `$_FILES` is treated as the client MIME type hint.
 * The actual MIME type is detected independently by
 * {@see \Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader} using `finfo`.
 */
final class NativePhpUploadAdapter
{
    private const UPLOAD_ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];

    /**
     * Build a DTO from a single `$_FILES` entry.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $filesEntry
     *
     * @throws InvalidImageInputException on upload error or missing required fields.
     */
    public static function fromFilesEntry(array $filesEntry): ImageFileInputDTO
    {
        $error = $filesEntry['error'];

        if ($error !== UPLOAD_ERR_OK) {
            $message = self::UPLOAD_ERROR_MESSAGES[$error]
                ?? sprintf('Unknown upload error code: %d', $error);

            throw InvalidImageInputException::uploadError(
                $filesEntry['name'],
                $error,
                $message,
            );
        }

        return new ImageFileInputDTO(
            originalName:   $filesEntry['name'],
            temporaryPath:  $filesEntry['tmp_name'],
            clientMimeType: $filesEntry['type'] !== '' ? $filesEntry['type'] : null,
            sizeBytes:      $filesEntry['size'],
        );
    }

    /**
     * Build a DTO directly from the `$_FILES` superglobal using a field name.
     *
     * @throws InvalidImageInputException on upload error or missing field.
     */
    public static function fromSuperGlobal(string $fieldName): ImageFileInputDTO
    {
        if (! isset($_FILES[$fieldName])) {
            throw InvalidImageInputException::uploadError($fieldName, UPLOAD_ERR_NO_FILE);
        }

        return self::fromFilesEntry($_FILES[$fieldName]);
    }
}
