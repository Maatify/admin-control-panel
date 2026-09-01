<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Validators;

use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Validators\MimeTypeValidator;
use PHPUnit\Framework\TestCase;

final class MimeTypeValidatorTest extends TestCase
{
    /**
     * Test that JPEG files with correct magic bytes are recognized.
     */
    public function testValidatesJpegByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/jpeg']);

        // Real JPEG magic bytes: FF D8 FF
        $jpegContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF";

        $file = $this->createMockFile($jpegContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that PNG files with correct magic bytes are recognized.
     */
    public function testValidatesPngByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/png']);

        // Real PNG magic bytes: 89 50 4E 47
        $pngContent = "\x89PNG\r\n\x1a\n";

        $file = $this->createMockFile($pngContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that GIF files are recognized.
     */
    public function testValidatesGifByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/gif']);

        // Real GIF magic bytes: 47 49 46
        $gifContent = "GIF89a";

        $file = $this->createMockFile($gifContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that WebM files are recognized.
     */
    public function testValidatesWebmByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['video/webm']);

        // Real WebM magic bytes: 1A 45 DF A3
        $webmContent = "\x1A\x45\xDF\xA3";

        $file = $this->createMockFile($webmContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that BMP files are recognized.
     */
    public function testValidatesBmpByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/bmp']);

        // Real BMP magic bytes: 42 4D
        $bmpContent = "BM" . str_repeat("\x00", 50);

        $file = $this->createMockFile($bmpContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that WebP files are recognized.
     */
    public function testValidatesWebpByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/webp']);

        // Real WebP magic bytes: RIFF ... WEBP
        $webpContent = "RIFF" . "\x00\x00\x00\x00" . "WEBP";

        $file = $this->createMockFile($webpContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that ICO files are recognized.
     */
    public function testValidatesIcoByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['image/x-icon']);

        // Real ICO magic bytes: 00 00 01 00
        $icoContent = "\x00\x00\x01\x00";

        $file = $this->createMockFile($icoContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that executable files disguised as JPEG are REJECTED.
     *
     * This is the critical security test - the main vulnerability we're protecting against.
     */
    public function testRejectsExecutableDisguisedAsJpeg(): void
    {
        $validator = new MimeTypeValidator(['image/jpeg']);

        // Executable file header (ELF) but named virus.jpg
        $executableContent = "\x7fELF" . str_repeat("\x00", 100);

        $file = $this->createMockFile($executableContent, 'virus.jpg');

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that PE (Windows) executables disguised as PNG are REJECTED.
     */
    public function testRejectsWindowsExecutableDisguisedAsPng(): void
    {
        $validator = new MimeTypeValidator(['image/png']);

        // Windows PE executable header: MZ
        $peContent = "MZ" . str_repeat("\x00", 100);

        $file = $this->createMockFile($peContent, 'trojan.png');

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that shell scripts disguised as images are REJECTED.
     */
    public function testRejectsShellScriptDisguisedAsImage(): void
    {
        $validator = new MimeTypeValidator(['image/jpeg']);

        // Shell script header: #!/bin/bash
        $scriptContent = "#!/bin/bash\nrm -rf /";

        $file = $this->createMockFile($scriptContent, 'script.jpg');

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that file with mismatched MIME type is rejected.
     */
    public function testRejectsWrongMimeType(): void
    {
        $validator = new MimeTypeValidator(['image/png']);

        // JPEG magic bytes but PNG is expected
        $jpegContent = "\xFF\xD8\xFF\xE0";

        $file = $this->createMockFile($jpegContent);

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that unknown file types are rejected when not in allowed list.
     */
    public function testRejectsUnknownMimeType(): void
    {
        $validator = new MimeTypeValidator(['image/jpeg']);

        // Random data (not a valid file type)
        $unknownContent = str_repeat("\x42", 100);

        $file = $this->createMockFile($unknownContent);

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that very short files (less than 4 bytes) are handled gracefully.
     */
    public function testHandlesShortFiles(): void
    {
        $validator = new MimeTypeValidator(['image/jpeg']);

        $shortContent = "AB";

        $file = $this->createMockFile($shortContent);

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Test that MP4 files are recognized by ftyp signature.
     */
    public function testValidatesMp4ByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['video/mp4']);

        // Real MP4: 00 00 00 XX ftyp
        $mp4Content = "\x00\x00\x00\x20ftypisom";

        $file = $this->createMockFile($mp4Content);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that AVI files are recognized by RIFF AVI signature.
     */
    public function testValidatesAviByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['video/x-msvideo']);

        // Real AVI: RIFF ... AVI
        $aviContent = "RIFF\x00\x00\x00\x00AVI ";

        $file = $this->createMockFile($aviContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test static helper for image MIME types.
     */
    public function testImageTypesHelper(): void
    {
        $types = MimeTypeValidator::imageTypes();

        $this->assertContains('image/jpeg', $types);
        $this->assertContains('image/png', $types);
        $this->assertContains('image/webp', $types);
        $this->assertCount(5, $types);
    }

    /**
     * Test static helper for video MIME types.
     */
    public function testVideoTypesHelper(): void
    {
        $types = MimeTypeValidator::videoTypes();

        $this->assertContains('video/mp4', $types);
        $this->assertContains('video/webm', $types);
        $this->assertCount(5, $types);
    }

    /**
     * Test static helper for audio MIME types.
     */
    public function testAudioTypesHelper(): void
    {
        $types = MimeTypeValidator::audioTypes();

        $this->assertContains('audio/mpeg', $types);
        $this->assertContains('audio/wav', $types);
        $this->assertContains('audio/ogg', $types);
        $this->assertCount(3, $types);
    }

    /**
     * Test that MP3 files with MPEG frame header are recognized.
     */
    public function testValidatesMp3ByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['audio/mpeg']);

        // MP3 magic bytes: FF FB (MPEG Audio Frame header)
        $mp3Content = "\xFF\xFB" . str_repeat("\x00", 100);

        $file = $this->createMockFile($mp3Content);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that MP3 files with ID3v2 tag are recognized.
     * Most REAL MP3 files start with ID3 metadata tag, not MPEG frame header.
     * This is the critical test - real MP3s from users will have ID3 tags.
     */
    public function testValidatesMp3WithId3Tag(): void
    {
        $validator = new MimeTypeValidator(['audio/mpeg']);

        // Real MP3 magic bytes: ID3v2 tag (49 44 33 = "ID3")
        // This is how MOST MP3 files actually start
        $mp3WithId3 = "ID3" . str_repeat("\x00", 100);

        $file = $this->createMockFile($mp3WithId3);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that WAV files with correct magic bytes are recognized.
     */
    public function testValidatesWavByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['audio/wav']);

        // Real WAV magic bytes: RIFF ... WAVE
        $wavContent = "RIFF\x00\x00\x00\x00WAVE";

        $file = $this->createMockFile($wavContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that OGG files with correct magic bytes are recognized.
     */
    public function testValidatesOggByMagicBytes(): void
    {
        $validator = new MimeTypeValidator(['audio/ogg']);

        // Real OGG magic bytes: OggS
        $oggContent = "OggS" . str_repeat("\x00", 100);

        $file = $this->createMockFile($oggContent);

        $validator->validate($file);
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test that executable files disguised as audio files are REJECTED.
     *
     * This protects against audio file spoofing attacks.
     * Uses MP3 as example (M4A/MP4 share same magic bytes, tested via AudioUploadServiceTest).
     */
    public function testRejectsExecutableDisguisedAsAudio(): void
    {
        $validator = new MimeTypeValidator(['audio/mpeg']);

        // PE executable header (Windows exe) but named song.mp3
        $peContent = "MZ" . str_repeat("\x00", 100);

        $file = $this->createMockFile($peContent, 'song.mp3');

        $this->expectException(InvalidFileException::class);
        $validator->validate($file);
    }

    /**
     * Create a mock UploadedFileInterface with content.
     */
    private function createMockFile(string $content, string $filename = 'test.jpg'): \Psr\Http\Message\UploadedFileInterface
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Failed to create memory stream');
        }
        fwrite($stream, $content);
        rewind($stream);

        $mockFile = $this->createMock(\Psr\Http\Message\UploadedFileInterface::class);
        $mockFile->method('getClientFilename')->willReturn($filename);
        $mockFile->method('getStream')->willReturn(
            new class($stream) implements \Psr\Http\Message\StreamInterface {
                private mixed $resource;

                public function __construct(mixed $resource)
                {
                    $this->resource = $resource;
                }

                public function __toString(): string
                {
                    return '';
                }

                public function close(): void
                {
                    if (is_resource($this->resource)) {
                        fclose($this->resource);
                    }
                }

                public function detach()
                {
                    return is_resource($this->resource) ? $this->resource : null;
                }

                public function getSize(): ?int
                {
                    if (!is_resource($this->resource)) {
                        return null;
                    }
                    $stats = fstat($this->resource);
                    return $stats['size'] ?? null;
                }

                public function tell(): int
                {
                    if (!is_resource($this->resource)) {
                        return 0;
                    }
                    return ftell($this->resource) ?: 0;
                }

                public function eof(): bool
                {
                    return !is_resource($this->resource) || feof($this->resource);
                }

                public function isSeekable(): bool
                {
                    return true;
                }

                public function seek(int $offset, int $whence = SEEK_SET): void
                {
                    if (!is_resource($this->resource)) {
                        return;
                    }
                    fseek($this->resource, $offset, $whence);
                }

                public function rewind(): void
                {
                    if (!is_resource($this->resource)) {
                        return;
                    }
                    rewind($this->resource);
                }

                public function isWritable(): bool
                {
                    return true;
                }

                public function write(string $string): int
                {
                    if (!is_resource($this->resource)) {
                        return 0;
                    }
                    $written = fwrite($this->resource, $string);
                    return $written === false ? 0 : $written;
                }

                public function isReadable(): bool
                {
                    return true;
                }

                public function read(int $length): string
                {
                    if (!is_resource($this->resource)) {
                        return '';
                    }
                    if ($length < 1) {
                        return '';
                    }
                    $data = fread($this->resource, $length);
                    return $data === false ? '' : $data;
                }

                public function getContents(): string
                {
                    if (!is_resource($this->resource)) {
                        return '';
                    }
                    $contents = stream_get_contents($this->resource);
                    return $contents === false ? '' : $contents;
                }

                public function getMetadata(?string $key = null): mixed
                {
                    if (!is_resource($this->resource)) {
                        return null;
                    }
                    $metadata = stream_get_meta_data($this->resource);
                    if ($key === null) {
                        return $metadata;
                    }
                    return $metadata[$key] ?? null;
                }
            }
        );

        return $mockFile;
    }
}
