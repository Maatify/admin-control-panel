<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\MimeTypeValidator;
use Maatify\Storage\Validators\SizeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Video upload service.
 *
 * Ready-to-use service for uploading videos with sensible defaults.
 * Can be customized per-call via optional parameters.
 *
 * Security: Always validates actual MIME type by inspecting file content (magic bytes).
 * This prevents attacks like virus.mp4 (executable disguised as video).
 *
 * Null handling:
 * - allowedExtensions: null = use defaults
 * - maxSizeBytes: null = no size validation
 */
final class VideoUploadService
{
    /** @var array<string> */
    private const ALLOWED_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi'];

    /** @var array<string> */
    private const ALLOWED_MIME_TYPES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
    ];

    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * Delete a stored video by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "tutorials/tutorial-abc123.mp4").
     */
    public function delete(string $path): void
    {
        $this->uploadService->delete($path);
    }

    /**
     * Upload a video with optional custom constraints.
     *
     * @param UploadedFileInterface $file The uploaded video file.
     * @param string $subfolder Destination subfolder (e.g. "testimonials", "tutorials").
     * @param array<string>|null $allowedExtensions null = use defaults (mp4, webm, mov, avi).
     * @param int|null $maxSizeBytes null = no size validation.
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
        ?string $customBaseName = null,
    ): StoredFile {
        $validators = [];

        // MIME Type validation (ALWAYS): Prevents attacks like virus.mp4
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

        $filename = $this->generateFilename($file, $customBaseName);
        $path = trim($subfolder, '/') . '/' . $filename;

        return $this->uploadService->upload($file, $path, ...$validators);
    }

    /**
     * Generate a unique filename for the uploaded video.
     *
     * @param string|null $customBaseName Optional semantic basename (e.g., "tutorial-slug", "webinar-123").
     *
     * @return string Filename with random suffix (e.g. "tutorial-abc123def456.mp4").
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
            $baseName ?: 'video',
            bin2hex(random_bytes(8)),
            strtolower($extension)
        );
    }
}
