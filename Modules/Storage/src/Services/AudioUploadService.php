<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\MimeTypeValidator;
use Maatify\Storage\Validators\SizeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Audio upload service.
 *
 * Ready-to-use service for uploading audio files with sensible defaults.
 * Can be customized per-call via optional parameters.
 *
 * Security: Always validates actual MIME type by inspecting file content (magic bytes).
 * This prevents attacks like virus.mp3 (executable disguised as audio).
 *
 * Null handling:
 * - allowedExtensions: null = use defaults
 * - maxSizeBytes: null = no size validation
 */
final class AudioUploadService
{
    /** @var array<string> */
    private const ALLOWED_EXTENSIONS = ['mp3', 'wav', 'ogg'];

    /** @var array<string, string> Maps file extensions to their corresponding MIME types */
    private const EXTENSION_TO_MIME = [
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
    ];

    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * Delete a stored audio file by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "podcasts/episode-5-abc123.mp3").
     */
    public function delete(string $path): void
    {
        $this->uploadService->delete($path);
    }

    /**
     * Upload an audio file with optional custom constraints.
     *
     * When specific extensions are provided, MIME type validation is restricted to match only
     * those extensions' corresponding MIME types. For example, allowedExtensions: ['mp3']
     * will only accept audio/mpeg content, not audio/wav or audio/ogg.
     *
     * @param UploadedFileInterface $file The uploaded audio file.
     * @param string $subfolder Destination subfolder (e.g. "podcasts", "music", "voiceovers").
     * @param array<string>|null $allowedExtensions null = use defaults (mp3, wav, ogg).
     *                                               Each extension maps to a specific MIME type.
     * @param int|null $maxSizeBytes null = no size validation.
     * @param string|null $customBaseName null = auto-generate from original filename. Semantic basename for business context.
     *
     * @return StoredFile DTO containing the storage path and resolved URL.
     *
     * @throws \Maatify\Storage\Exception\FileUploadException If upload error.
     * @throws \Maatify\Storage\Exception\InvalidFileException If validation fails (including MIME/extension mismatch).
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
        ?string $customBaseName = null,
    ): StoredFile {
        $validators = [];

        // Normalize and validate extensions
        // User may provide: ['MP3'], ['.mp3'], ['mp3'], or unsupported ['aac']
        // We normalize to lowercase without dots, then validate and map to MIME types
        $extensions = $allowedExtensions ?? self::ALLOWED_EXTENSIONS;

        $normalizedExtensions = [];
        $allowedMimeTypes = [];

        foreach ($extensions as $extension) {
            // Normalize: lowercase and remove leading dots
            $normalized = strtolower(ltrim($extension, '.'));

            // Validate that extension is supported
            if (!isset(self::EXTENSION_TO_MIME[$normalized])) {
                throw InvalidFileException::unsupportedExtension(
                    $extension,
                    array_keys(self::EXTENSION_TO_MIME)
                );
            }

            $normalizedExtensions[] = $normalized;
            $allowedMimeTypes[] = self::EXTENSION_TO_MIME[$normalized];
        }

        // Remove duplicates while preserving order
        $normalizedExtensions = array_values(array_unique($normalizedExtensions));
        $allowedMimeTypes = array_values(array_unique($allowedMimeTypes));

        // MIME Type validation (ALWAYS): Prevents attacks like virus.mp3
        // This reads file content (magic bytes), not just extension
        $validators[] = new MimeTypeValidator($allowedMimeTypes);

        // Extension validation: Ensures filename has an allowed extension
        $validators[] = new ExtensionValidator($normalizedExtensions);

        // Size: null = skip validation
        if ($maxSizeBytes !== null) {
            $validators[] = new SizeValidator($maxSizeBytes);
        }

        $filename = $this->generateFilename($file, $customBaseName);
        $path = trim($subfolder, '/') . '/' . $filename;

        return $this->uploadService->upload($file, $path, ...$validators);
    }

    /**
     * Generate a unique filename for the uploaded audio file.
     *
     * @param string|null $customBaseName Optional semantic basename (e.g., "podcast-episode-5", "dua-001").
     *
     * @return string Filename with random suffix (e.g. "podcast-episode-5-abc123def456.mp3").
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
            $baseName ?: 'audio',
            bin2hex(random_bytes(8)),
            strtolower($extension)
        );
    }
}
