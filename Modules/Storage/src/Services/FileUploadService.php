<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Contracts\StorageAdapterInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class FileUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly StorageAdapterInterface $storage,
    ) {}

    /**
     * @param UploadedFileInterface $uploadedFile
     * @param string                $subfolder   e.g. "products" or "avatars/2025"
     * @param string|null           $baseName    e.g. product slug — used as filename prefix
     *
     * @return string  Publicly accessible URL returned by the active storage adapter
     */
    public function handleUpload(
        UploadedFileInterface $uploadedFile,
        string $subfolder,
        ?string $baseName = null,
    ): string {
        $this->assertNoUploadError($uploadedFile);

        $extension = $this->resolveAndValidateExtension($uploadedFile);
        $filename  = $this->generateFilename($baseName, $extension);

        $destinationPath = trim($subfolder, '/') . '/' . $filename;

        return $this->storage->store($uploadedFile, $destinationPath);
    }

    private function assertNoUploadError(UploadedFileInterface $file): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload error code: ' . $file->getError());
        }
    }

    private function resolveAndValidateExtension(UploadedFileInterface $file): string
    {
        $originalFilename = $file->getClientFilename();

        if (!$originalFilename) {
            throw new RuntimeException('Invalid file name provided by client.');
        }

        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException(
                'Invalid file extension. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        return $extension;
    }

    private function generateFilename(?string $baseName, string $extension): string
    {
        $sanitized  = $baseName
            ? preg_replace('/[^a-z0-9-]/', '', strtolower($baseName))
            : 'file';

        $randomPart = bin2hex(random_bytes(8));

        return sprintf('%s-%s.%s', $sanitized, $randomPart, $extension);
    }
}
