<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for Storage module tests.
 *
 * Provides common utilities and setup for all Storage module tests.
 */
abstract class StorageModuleTestCase extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a unique temp directory for this test
        $this->tempDir = STORAGE_TESTS_TEMP_PATH . '/' . uniqid('test_', true);
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temp directory
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Create a mock PSR-7 UploadedFile for testing.
     *
     * Automatically adds proper magic bytes based on filename extension
     * to ensure MIME type validation passes, but only if the content
     * doesn't already have them (to avoid corrupting real file data).
     *
     * @param string $content File content
     * @param string $filename Original filename
     * @param string $mimeType MIME type
     * @param int $error Upload error code (UPLOAD_ERR_OK by default)
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockUploadedFile(
        string $content = 'test content',
        string $filename = 'test.jpg',
        string $mimeType = 'image/jpeg',
        int $error = UPLOAD_ERR_OK,
    ): \Psr\Http\Message\UploadedFileInterface {
        // Only inject magic bytes if content is obviously dummy test data
        // Real file data (like JPEG from GD) will already have correct headers
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $finalContent = $this->injectMagicBytesIfNeeded($content, $extension);

        $tempFile = tempnam($this->tempDir, 'upload_');
        if ($tempFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        file_put_contents($tempFile, $finalContent);

        $mock = $this->createMock(\Psr\Http\Message\UploadedFileInterface::class);
        $mock->method('getStream')->willReturnCallback(static function () use ($tempFile) {
            $stream = fopen($tempFile, 'r');
            if ($stream === false) {
                throw new \RuntimeException("Cannot open temp file: $tempFile");
            }
            return new class($stream) implements \Psr\Http\Message\StreamInterface {
                /** @var resource|null */
                private mixed $stream;

                /**
                 * @param resource $stream
                 */
                public function __construct(mixed $stream)
                {
                    $this->stream = $stream;
                }

                public function __toString(): string
                {
                    if (!is_resource($this->stream)) {
                        return '';
                    }
                    $contents = stream_get_contents($this->stream);
                    return (string) ($contents !== false ? $contents : '');
                }

                public function close(): void
                {
                    if (is_resource($this->stream)) {
                        fclose($this->stream);
                    }
                }

                public function detach(): mixed
                {
                    $stream = $this->stream;
                    $this->stream = null;
                    return is_resource($stream) ? $stream : null;
                }

                public function getSize(): ?int
                {
                    $size = filesize($GLOBALS['_SERVER']['__UPLOAD_FILE__'] ?? '');
                    return $size !== false ? $size : null;
                }

                public function tell(): int
                {
                    if (!is_resource($this->stream)) {
                        return 0;
                    }
                    $pos = ftell($this->stream);
                    return $pos !== false ? $pos : 0;
                }

                public function eof(): bool
                {
                    if (!is_resource($this->stream)) {
                        return true;
                    }
                    return feof($this->stream);
                }

                public function isSeekable(): bool
                {
                    return true;
                }

                public function seek(int $offset, int $whence = SEEK_SET): void
                {
                    if (is_resource($this->stream)) {
                        fseek($this->stream, $offset, $whence);
                    }
                }

                public function rewind(): void
                {
                    if (is_resource($this->stream)) {
                        rewind($this->stream);
                    }
                }

                public function isWritable(): bool
                {
                    return true;
                }

                public function write(string $string): int
                {
                    if (!is_resource($this->stream)) {
                        return 0;
                    }
                    $written = fwrite($this->stream, $string);
                    return $written !== false ? $written : 0;
                }

                public function isReadable(): bool
                {
                    return true;
                }

                public function read(int $length): string
                {
                    if (!is_resource($this->stream)) {
                        return '';
                    }
                    if ($length < 1) {
                        return '';
                    }
                    $data = fread($this->stream, $length);
                    return $data !== false ? $data : '';
                }

                public function getContents(): string
                {
                    if (!is_resource($this->stream)) {
                        return '';
                    }
                    $contents = stream_get_contents($this->stream);
                    return $contents !== false ? $contents : '';
                }

                public function getMetadata(?string $key = null): mixed
                {
                    return null;
                }
            };
        });

        $mock->method('getClientFilename')->willReturn($filename);
        $mock->method('getClientMediaType')->willReturn($mimeType);
        $mock->method('getError')->willReturn($error);
        // Return size of final content (including any prepended magic bytes)
        $mock->method('getSize')->willReturn(strlen($finalContent));

        return $mock;
    }

    /**
     * Intelligently inject magic bytes only if content appears to be dummy test data.
     *
     * Safe approach: Only prepend magic bytes if:
     * 1. Content is very short (< 32 bytes) - clearly dummy test data
     * 2. OR content doesn't already start with any known file signature
     *
     * This protects real file data (e.g., JPEG from GD) which already has correct headers.
     *
     * @param string $content File content
     * @param string $extension File extension
     *
     * @return string Final content (with magic bytes if needed)
     */
    private function injectMagicBytesIfNeeded(string $content, string $extension): string
    {
        // Real file data is usually > 32 bytes, dummy test content is shorter
        if (strlen($content) > 32) {
            return $content; // Assume it's real data, don't touch it
        }

        // For short content, check if it already has magic bytes
        $magicBytes = $this->getMagicBytesForExtension($extension);
        if ($magicBytes === '') {
            return $content; // No magic bytes for this type
        }

        // Use minimal signature for check to be flexible with real files
        $minimal = $this->getMinimalSignature($extension);
        if ($minimal !== '' && str_starts_with($content, $minimal)) {
            return $content; // Already has signature
        }

        // Inject full magic bytes for dummy test data
        return $magicBytes . $content;
    }

    /**
     * Get minimal signature for flexible comparison (first 3-4 bytes only).
     *
     * @param string $extension File extension
     *
     * @return string Minimal signature
     */
    private function getMinimalSignature(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg', 'jpe' => "\xFF\xD8\xFF",  // JPEG: just first 3 bytes
            'png' => "\x89PNG",  // PNG: 4 bytes
            'gif' => "GIF",  // GIF: just GIF
            'webp' => "RIFF",  // WebP: RIFF
            'bmp' => "BM",  // BMP: BM
            'mp4' => "\x00\x00\x00",  // MP4: first 3 null bytes
            'webm' => "\x1A\x45",  // WebM: EBML start
            'avi' => "RIFF",  // AVI: RIFF
            'mov' => "\x00\x00\x00",  // MOV: first 3 null bytes
            'mkv' => "\x1A\x45",  // Matroska: EBML start
            'mp3' => "\xFF\xFB",  // MP3: MPEG audio frame header
            'wav' => "RIFF",  // WAV: RIFF
            'ogg' => "OggS",  // OGG: OggS
            default => '',
        };
    }

    /**
     * Get proper magic bytes (file signature) based on file extension.
     *
     * Provides complete, valid magic bytes that will pass MIME validation.
     * These are injected ONLY if the content doesn't already have them.
     *
     * @param string $extension File extension (jpg, png, mp4, etc.)
     *
     * @return string Magic bytes for the file type
     */
    private function getMagicBytesForExtension(string $extension): string
    {
        return match ($extension) {
            // JPEG: Full JFIF header for valid detection
            'jpg', 'jpeg', 'jpe' => "\xFF\xD8\xFF\xE0\x00\x10JFIF",
            // PNG: Standard PNG signature
            'png' => "\x89PNG\r\n\x1a\n",
            // GIF: GIF89a or GIF87a
            'gif' => "GIF89a",
            // WebP: RIFF + WEBP signature
            'webp' => "RIFF\x00\x00\x00\x00WEBP",
            // BMP: BM signature
            'bmp' => "BM",
            // ICO: Standard ICO header
            'ico' => "\x00\x00\x01\x00",
            // MP4: ftyp signature
            'mp4' => "\x00\x00\x00\x20ftypisom",
            // WebM: EBML signature
            'webm' => "\x1A\x45\xDF\xA3",
            // AVI: RIFF + AVI signature
            'avi' => "RIFF\x00\x00\x00\x00AVI ",
            // MOV: QuickTime signature
            'mov' => "\x00\x00\x00\x20mdat",
            // Matroska: EBML signature (same as WebM)
            'mkv' => "\x1A\x45\xDF\xA3",
            // MP3: MPEG Audio Frame header (VBR)
            'mp3' => "\xFF\xFB",
            // WAV: RIFF + WAVE signature
            'wav' => "RIFF\x00\x00\x00\x00WAVE",
            // OGG: OggS signature
            'ogg' => "OggS",
            default => '',  // Unknown - no magic bytes
        };
    }

    /**
     * Create a mock JPEG image with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockJpegFile(string $filename = 'test.jpg'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real JPEG magic bytes: FF D8 FF E0 00 10 JFIF
        $jpegContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF" . str_repeat("\x00", 1000);

        return $this->createMockUploadedFile($jpegContent, $filename, 'image/jpeg');
    }

    /**
     * Create a mock PNG image with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockPngFile(string $filename = 'test.png'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real PNG magic bytes: 89 50 4E 47 0D 0A 1A 0A
        $pngContent = "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 1000);

        return $this->createMockUploadedFile($pngContent, $filename, 'image/png');
    }

    /**
     * Create a mock WebP image with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockWebpFile(string $filename = 'test.webp'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real WebP magic bytes: RIFF ... WEBP
        $webpContent = "RIFF\x00\x00\x00\x00WEBP" . str_repeat("\x00", 1000);

        return $this->createMockUploadedFile($webpContent, $filename, 'image/webp');
    }

    /**
     * Create a mock GIF image with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockGifFile(string $filename = 'test.gif'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real GIF magic bytes: 47 49 46 38 39 61 (GIF89a)
        $gifContent = "GIF89a" . str_repeat("\x00", 1000);

        return $this->createMockUploadedFile($gifContent, $filename, 'image/gif');
    }

    /**
     * Create a mock MP4 video with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockMp4File(string $filename = 'test.mp4'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real MP4 magic bytes: 00 00 00 20 ftyp
        $mp4Content = "\x00\x00\x00\x20ftypisom" . str_repeat("\x00", 5000);

        return $this->createMockUploadedFile($mp4Content, $filename, 'video/mp4');
    }

    /**
     * Create a mock WebM video with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockWebmFile(string $filename = 'test.webm'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real WebM magic bytes: 1A 45 DF A3
        $webmContent = "\x1A\x45\xDF\xA3" . str_repeat("\x00", 5000);

        return $this->createMockUploadedFile($webmContent, $filename, 'video/webm');
    }

    /**
     * Create a mock AVI video with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockAviFile(string $filename = 'test.avi'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real AVI magic bytes: RIFF ... AVI
        $aviContent = "RIFF\x00\x00\x00\x00AVI " . str_repeat("\x00", 5000);

        return $this->createMockUploadedFile($aviContent, $filename, 'video/x-msvideo');
    }

    /**
     * Create a mock MOV video with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockMovFile(string $filename = 'test.mov'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real MOV magic bytes: 00 00 00 20 ftyp (similar to MP4 but parsed as quicktime)
        $movContent = "\x00\x00\x00\x20mdat" . str_repeat("\x00", 5000);

        return $this->createMockUploadedFile($movContent, $filename, 'video/quicktime');
    }

    /**
     * Create a mock MP3 audio with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockMp3File(string $filename = 'test.mp3'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real MP3 magic bytes: FF FB (MPEG Audio Frame header - VBR)
        $mp3Content = "\xFF\xFB" . str_repeat("\x00", 3000);

        return $this->createMockUploadedFile($mp3Content, $filename, 'audio/mpeg');
    }

    /**
     * Create a mock WAV audio with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockWavFile(string $filename = 'test.wav'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real WAV magic bytes: RIFF + WAVE signature
        $wavContent = "RIFF\x00\x00\x00\x00WAVE" . str_repeat("\x00", 3000);

        return $this->createMockUploadedFile($wavContent, $filename, 'audio/wav');
    }

    /**
     * Create a mock OGG audio with proper magic bytes for MIME type validation.
     *
     * @param string $filename Original filename
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createMockOggFile(string $filename = 'test.ogg'): \Psr\Http\Message\UploadedFileInterface
    {
        // Real OGG magic bytes: OggS
        $oggContent = "OggS" . str_repeat("\x00", 3000);

        return $this->createMockUploadedFile($oggContent, $filename, 'audio/ogg');
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

}
