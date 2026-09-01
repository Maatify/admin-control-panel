<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\FileUploadException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Core file upload service.
 *
 * Handles the mechanics of uploading a file after validation.
 * No hardcoded validation rules — validators are pluggable.
 *
 * Per Maatify Module Building Standard section 12 — services orchestrate,
 * they don't validate directly.
 */
final readonly class FileUploadService
{
    public function __construct(
        private StorageAdapterInterface $storage,
    ) {}

    /**
     * Delete a stored file by its relative path.
     *
     * Delegates directly to the adapter. Silently ignores non-existent files.
     *
     * @param string $path The relative path returned by upload() (e.g. "products/slug-abc123.jpg").
     */
    public function delete(string $path): void
    {
        $this->storage->delete($path);
    }

    /**
     * Upload a file with optional validators.
     *
     * @param UploadedFileInterface $file The uploaded file from the request.
     * @param string $destinationPath The destination path (e.g. "images/products/slug-abc123.jpg").
     * @param FileValidator ...$validators Optional validators (if none, no validation is performed).
     *
     * @return StoredFile DTO containing the storage path and resolved URL.
     *
     * @throws \Maatify\Storage\Exception\FileUploadException If the file has a PHP upload error.
     * @throws \Maatify\Storage\Exception\InvalidFileException If any validator fails.
     */
    public function upload(
        UploadedFileInterface $file,
        string $destinationPath,
        FileValidator ...$validators
    ): StoredFile {
        // Check for PHP upload errors first
        $this->assertNoUploadError($file);

        // Apply all validators (if any provided)
        foreach ($validators as $validator) {
            $validator->validate($file);
        }

        // Store the file
        return $this->storage->store($file, $destinationPath);
    }

    /**
     * Check if file has a PHP upload error.
     *
     * @throws FileUploadException If upload error is present.
     */
    private function assertNoUploadError(UploadedFileInterface $file): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw FileUploadException::fromErrorCode($file->getError());
        }
    }
}
