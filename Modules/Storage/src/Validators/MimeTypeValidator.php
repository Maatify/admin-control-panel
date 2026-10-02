<?php

declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exception\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates file MIME type by inspecting actual file content (magic bytes).
 *
 * This is much more secure than extension validation as it reads the actual
 * file header to determine true type, preventing attacks like virus.jpg
 * (executable disguised as image).
 *
 * ⚠️ NOTE: This validator detects many formats, but ImageUploadService and
 * VideoUploadService have more restrictive defaults. See their docs for
 * what's actually accepted by default.
 *
 * Supported MIME types for images:
 * - image/jpeg  (.jpg, .jpeg)
 * - image/png   (.png)
 * - image/webp  (.webp)
 * - image/gif   (.gif)
 * - image/bmp   (.bmp)
 * - image/heic  (.heic)
 * - image/heif  (.heif)
 * - image/x-icon (.ico)
 *
 * Supported MIME types for videos:
 * - video/mp4   (.mp4)
 * - video/quicktime (.mov)
 * - video/x-msvideo (.avi)
 * - video/webm  (.webm)
 * - video/x-matroska (.mkv)
 * - application/x-mpegURL (.m3u8)
 *
 * ⚠️ Known limitation: WebM and Matroska (MKV) share the same EBML magic bytes
 * (1A 45 DF A3). This validator returns 'video/webm' for both and cannot
 * distinguish between them. If you need to accept only one, use custom
 * extension validation or provide your own validator.
 *
 * Supported MIME types for audio:
 * - audio/mpeg  (.mp3)
 * - audio/wav   (.wav)
 * - audio/ogg   (.ogg, .oga)
 * - audio/mp4   (.m4a)
 * - audio/aac   (.aac)
 * - audio/x-caf (.caf)
 */
final class MimeTypeValidator implements FileValidator
{
    /**
     * Map of allowed MIME types
     *
     * @param array<string> $allowedMimeTypes e.g. ['image/jpeg', 'image/png']
     */
    public function __construct(
        private readonly array $allowedMimeTypes,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileException If MIME type is not allowed or cannot be determined.
     */
    public function validate(UploadedFileInterface $file): void
    {
        $this->validateAndDetect($file);
    }

    /**
     * Detects the content MIME type and validates it against this validator's allowlist.
     *
     * Returning the detected type lets callers make content-authoritative decisions
     * after the same validation that protects the upload boundary. Client filename and
     * client-provided MIME metadata are never consulted.
     *
     * @throws InvalidFileException If MIME type is not allowed or cannot be determined.
     */
    public function validateAndDetect(UploadedFileInterface $file): string
    {
        $mimeType = $this->detectMimeType($file);

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw InvalidFileException::unsupportedMimeType(
                $mimeType,
                $this->allowedMimeTypes
            );
        }

        return $mimeType;
    }

    /**
     * Detect actual MIME type from file content (magic bytes).
     *
     * This method reads the file header to determine the true type,
     * which cannot be spoofed by renaming files.
     *
     * @return string The detected MIME type or 'application/octet-stream' if unknown
     */
    private function detectMimeType(UploadedFileInterface $file): string
    {
        $stream = $file->getStream();
        $stream->rewind();

        // Read first 512 bytes (sufficient for magic bytes detection)
        $header = $stream->read(512);
        $stream->rewind();

        if (strlen($header) < 4) {
            return 'application/octet-stream';
        }

        // Check magic bytes (file signatures)
        $bytes = unpack('C4', $header);
        if ($bytes === false) {
            return 'application/octet-stream';
        }

        [$byte1, $byte2, $byte3, $byte4] = array_values($bytes);

        // JPEG: FF D8 FF
        if ($byte1 === 0xFF && $byte2 === 0xD8 && $byte3 === 0xFF) {
            return 'image/jpeg';
        }

        // PNG: 89 50 4E 47
        if ($byte1 === 0x89 && $byte2 === 0x50 && $byte3 === 0x4E && $byte4 === 0x47) {
            return 'image/png';
        }

        // GIF: 47 49 46
        if ($byte1 === 0x47 && $byte2 === 0x49 && $byte3 === 0x46) {
            return 'image/gif';
        }

        // BMP: 42 4D
        if ($byte1 === 0x42 && $byte2 === 0x4D) {
            return 'image/bmp';
        }

        // WebP: RIFF ... WEBP
        if ($byte1 === 0x52 && $byte2 === 0x49 && $byte3 === 0x46 && $byte4 === 0x46) {
            // Check for WEBP signature at offset 8
            $stream->seek(8);
            $webpSig = $stream->read(4);
            $stream->rewind();
            if ($webpSig === 'WEBP') {
                return 'image/webp';
            }
        }

        // ISO Base Media File Format: MP4, MOV, M4A, HEIC and HEIF.
        if ($byte1 === 0x00 && $byte2 === 0x00 && $byte3 === 0x00) {
            $isoBmffMimeType = $this->detectIsoBmffMimeType($header);
            if ($isoBmffMimeType !== null) {
                return $isoBmffMimeType;
            }
        }

        // MOV legacy atoms: 00 00 00 XX mdat/wide.
        if ($byte1 === 0x00 && $byte2 === 0x00 && $byte3 === 0x00) {
            $stream->seek(4);
            $sig = $stream->read(4);
            $stream->rewind();
            if (str_starts_with($sig, 'mdat') || str_starts_with($sig, 'wide')) {
                return 'video/quicktime';
            }
        }

        // WebM: 1A 45 DF A3
        if ($byte1 === 0x1A && $byte2 === 0x45 && $byte3 === 0xDF && $byte4 === 0xA3) {
            return 'video/webm';
        }

        // AVI: 52 49 46 46 (RIFF) ... 41 56 49 20 (AVI )
        if ($byte1 === 0x52 && $byte2 === 0x49 && $byte3 === 0x46 && $byte4 === 0x46) {
            $stream->seek(8);
            $aviSig = $stream->read(4);
            $stream->rewind();
            if ($aviSig === 'AVI ') {
                return 'video/x-msvideo';
            }
        }

        // Matroska (MKV): 1A 45 DF A3
        if ($byte1 === 0x1A && $byte2 === 0x45 && $byte3 === 0xDF && $byte4 === 0xA3) {
            return 'video/x-matroska';
        }

        // ICO: 00 00 01 00
        if ($byte1 === 0x00 && $byte2 === 0x00 && $byte3 === 0x01 && $byte4 === 0x00) {
            return 'image/x-icon';
        }

        // ─── AUDIO FORMATS ──────────────────────────────────────────────────

        // AAC: ADTS frame header (FFF1/FFF9 and compatible MPEG-2 variants).
        if ($byte1 === 0xFF && ($byte2 & 0xF6) === 0xF0) {
            return 'audio/aac';
        }

        // MP3: ID3 tag (49 44 33 = "ID3") or MPEG frame header
        // ID3 is MORE COMMON in real MP3 files - most actual MP3s start with ID3v2 tag
        if ($byte1 === 0x49 && $byte2 === 0x44 && $byte3 === 0x33) {
            return 'audio/mpeg';
        }

        // MP3: MPEG-1 Audio Frame header (FF FB or FF FA) - less common as start byte
        if ($byte1 === 0xFF && ($byte2 === 0xFB || $byte2 === 0xFA)) {
            return 'audio/mpeg';
        }

        // WAV: RIFF ... WAVE
        if ($byte1 === 0x52 && $byte2 === 0x49 && $byte3 === 0x46 && $byte4 === 0x46) {
            // Check for WAVE signature at offset 8
            $stream->seek(8);
            $waveSig = $stream->read(4);
            $stream->rewind();
            if ($waveSig === 'WAVE') {
                return 'audio/wav';
            }
        }

        // OGG: OggS
        if ($byte1 === 0x4F && $byte2 === 0x67 && $byte3 === 0x67 && $byte4 === 0x53) {
            return 'audio/ogg';
        }

        // CAF: Core Audio Format.
        if (substr($header, 0, 4) === 'caff') {
            return 'audio/x-caf';
        }

        // Unknown type
        return 'application/octet-stream';
    }

    /**
     * Classify an ISO-BMFF file from its ftyp major and compatible brands.
     *
     * HEIC/HEIF files can use a generic major brand while carrying the
     * identifying HEIF brand only in the compatible-brands list, so checking
     * the major brand alone would incorrectly classify them as MP4.
     */
    private function detectIsoBmffMimeType(string $header): ?string
    {
        if (strlen($header) < 12 || substr($header, 4, 4) !== 'ftyp') {
            return null;
        }

        $sizeParts = unpack('NboxSize', substr($header, 0, 4));
        if ($sizeParts === false) {
            return null;
        }

        $boxSize = (int) $sizeParts['boxSize'];
        if ($boxSize !== 0 && $boxSize !== 1 && $boxSize < 16) {
            return null;
        }

        $boxEnd  = $boxSize === 0 || $boxSize === 1
            ? strlen($header)
            : min($boxSize, strlen($header));

        $brands = [substr($header, 8, 4)];
        if ($boxEnd >= 16) {
            for ($offset = 16; $offset + 4 <= $boxEnd; $offset += 4) {
                $brands[] = substr($header, $offset, 4);
            }
        }

        // HEIC-specific brands take precedence over generic HEIF brands.
        if (array_intersect($brands, ['heic', 'heis', 'heix', 'hevc', 'hevs', 'hevx']) !== []) {
            return 'image/heic';
        }

        if (array_intersect($brands, ['mif1', 'msf1']) !== []) {
            return 'image/heif';
        }

        if (array_intersect($brands, ['M4A ', 'M4B ', 'M4P ']) !== []) {
            return 'audio/mp4';
        }

        if (in_array('qt  ', $brands, true)) {
            return 'video/quicktime';
        }

        return 'video/mp4';
    }

    /**
     * Get recommended MIME types for images.
     *
     * @return array<string>
     */
    public static function imageTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/bmp',
            'image/heic',
            'image/heif',
        ];
    }

    /**
     * Get recommended MIME types for videos.
     *
     * @return array<string>
     */
    public static function videoTypes(): array
    {
        return [
            'video/mp4',
            'video/quicktime',
            'video/webm',
            'video/x-msvideo',
            'video/x-matroska',
        ];
    }

    /**
     * Get recommended MIME types for audio.
     *
     * @return array<string>
     */
    public static function audioTypes(): array
    {
        return [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/mp4',
            'audio/aac',
            'audio/x-caf',
        ];
    }
}
