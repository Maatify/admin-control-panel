<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\ImageDimensionsValidator;
use Maatify\Storage\Validators\MimeTypeValidator;
use Maatify\Storage\Validators\SizeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Image upload service.
 *
 * Ready-to-use service for uploading images with sensible defaults.
 * Can be customized per-call via optional parameters.
 *
 * Security: Always validates actual MIME type by inspecting file content (magic bytes).
 * This prevents attacks like virus.jpg (executable disguised as image).
 *
 * Null handling:
 * - allowedExtensions: null = use defaults
 * - maxSizeBytes: null = no size validation
 * - dimensions: null = no dimension validation
 */
final class ImageUploadService
{
    /** @var array<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var array<string> */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
    ];

    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * Delete a stored image by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "products/slug-abc123.jpg").
     */
    public function delete(string $path): void
    {
        $this->uploadService->delete($path);
    }

    /**
     * Upload an image with optional custom constraints.
     *
     * @param UploadedFileInterface $file The uploaded image file.
     * @param string $subfolder Destination subfolder (e.g. "products", "avatars").
     * @param array<string>|null $allowedExtensions null = use defaults (jpg, jpeg, png, webp).
     * @param int|null $maxSizeBytes null = no size validation.
     * @param ImageDimensions|null $dimensions null = no dimension validation.
     * @param string|null $customBaseName null = auto-generate from original filename. Semantic basename for business context.
     *
     * @return StoredFile DTO containing the storage path and resolved URL.
     *
     * @throws \Maatify\Storage\Exception\FileUploadException If upload error.
     * @throws \Maatify\Storage\Exception\InvalidFileException If validation fails.
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
        ?ImageDimensions $dimensions = null,
        ?string $customBaseName = null,
    ): StoredFile {
        $validators = [];

        // MIME Type validation (ALWAYS): Prevents attacks like virus.jpg
        // This reads file content (magic bytes), not just extension
        $validators[] = new MimeTypeValidator(self::ALLOWED_MIME_TYPES);

        // Extension validation: null = use defaults (redundant with MIME, but fast)
        $validators[] = new ExtensionValidator(
            $allowedExtensions ?? self::ALLOWED_EXTENSIONS
        );

        // Size: null = skip validation
        if ($maxSizeBytes !== null) {
            $validators[] = new SizeValidator($maxSizeBytes);
        }

        // Dimensions: null = skip validation
        if ($dimensions !== null) {
            $validators[] = new ImageDimensionsValidator($dimensions);
        }

        $filename = $this->generateFilename($file, $customBaseName);
        $path = trim($subfolder, '/') . '/' . $filename;

        return $this->uploadService->upload($file, $path, ...$validators);
    }

    /**
     * Generate a unique filename for the uploaded image.
     *
     * @param string|null $customBaseName Optional semantic basename (e.g., "product-slug", "payment-method-123").
     *
     * @return string Filename with random suffix (e.g. "product-slug-abc123def456.jpg").
     */
    private function generateFilename(UploadedFileInterface $file, ?string $customBaseName = null): string
    {
        $extension = pathinfo($file->getClientFilename() ?? 'file', PATHINFO_EXTENSION);

        // Use custom basename if provided, otherwise extract from original filename
        if ($customBaseName !== null) {
            $baseName = preg_replace('/[^a-z0-9-]/', '', strtolower($customBaseName));
        } else {
            $originalName = $file->getClientFilename() ?? 'file';
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $baseName = preg_replace('/[^a-z0-9-]/', '', strtolower($baseName));
        }

        return sprintf(
            '%s-%s.%s',
            $baseName ?: 'image',
            bin2hex(random_bytes(8)),
            strtolower($extension)
        );
    }
}
