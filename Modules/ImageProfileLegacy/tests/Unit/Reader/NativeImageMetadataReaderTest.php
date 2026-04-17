<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Reader;

use ImageProfileLegacy\tests\Fixtures\TestImageFactory;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\Exception\ImageMetadataReadException;
use Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader::class)]
final class NativeImageMetadataReaderTest extends TestCase
{
    private NativeImageMetadataReader $reader;

    protected function setUp(): void
    {
        $this->reader = new NativeImageMetadataReader();
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    private function makeInput(string $path, string $name = 'test.jpg'): ImageFileInputDTO
    {
        return new ImageFileInputDTO(
            originalName:   $name,
            temporaryPath:  $path,
            clientMimeType: null,
            sizeBytes:      filesize($path) ?: 0,
        );
    }

    // -------------------------------------------------------------------------
    // JPEG
    // -------------------------------------------------------------------------

    public function test_reads_jpeg_dimensions(): void
    {
        $path     = TestImageFactory::jpeg(320, 240);
        $metadata = $this->reader->read($this->makeInput($path, 'photo.jpg'));

        self::assertSame(320, $metadata->width);
        self::assertSame(240, $metadata->height);
    }

    public function test_reads_jpeg_mime_type(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.jpg'));

        self::assertSame('image/jpeg', $metadata->detectedMimeType);
    }

    public function test_reads_jpeg_extension(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.jpg'));

        self::assertSame('jpg', $metadata->detectedExtension);
    }

    public function test_reads_jpeg_size_bytes(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.jpg'));

        self::assertSame(filesize($path), $metadata->sizeBytes);
    }

    // -------------------------------------------------------------------------
    // PNG
    // -------------------------------------------------------------------------

    public function test_reads_png_dimensions(): void
    {
        $path     = TestImageFactory::png(640, 480);
        $metadata = $this->reader->read($this->makeInput($path, 'photo.png'));

        self::assertSame(640, $metadata->width);
        self::assertSame(480, $metadata->height);
    }

    public function test_reads_png_mime_type(): void
    {
        $path     = TestImageFactory::png();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.png'));

        self::assertSame('image/png', $metadata->detectedMimeType);
    }

    public function test_reads_png_extension(): void
    {
        $path     = TestImageFactory::png();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.png'));

        self::assertSame('png', $metadata->detectedExtension);
    }

    // -------------------------------------------------------------------------
    // WebP
    // -------------------------------------------------------------------------

    public function test_reads_webp_dimensions(): void
    {
        $path     = TestImageFactory::webp(800, 600);
        $metadata = $this->reader->read($this->makeInput($path, 'photo.webp'));

        self::assertSame(800, $metadata->width);
        self::assertSame(600, $metadata->height);
    }

    public function test_reads_webp_mime_type(): void
    {
        $path     = TestImageFactory::webp();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.webp'));

        self::assertSame('image/webp', $metadata->detectedMimeType);
    }

    public function test_reads_webp_extension(): void
    {
        $path     = TestImageFactory::webp();
        $metadata = $this->reader->read($this->makeInput($path, 'photo.webp'));

        self::assertSame('webp', $metadata->detectedExtension);
    }

    // -------------------------------------------------------------------------
    // Non-image file — must throw
    // -------------------------------------------------------------------------

    public function test_throws_on_non_image_file(): void
    {
        $this->expectException(ImageMetadataReadException::class);

        $path = TestImageFactory::notAnImage();
        $this->reader->read($this->makeInput($path, 'not_an_image.txt'));
    }

    // -------------------------------------------------------------------------
    // Metadata contract — returned DTO is fully populated
    // -------------------------------------------------------------------------

    public function test_metadata_dto_has_positive_dimensions(): void
    {
        $path     = TestImageFactory::jpeg(100, 200);
        $metadata = $this->reader->read($this->makeInput($path, 'test.jpg'));

        self::assertGreaterThan(0, $metadata->width);
        self::assertGreaterThan(0, $metadata->height);
    }

    public function test_metadata_dto_has_positive_size_bytes(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'test.jpg'));

        self::assertGreaterThan(0, $metadata->sizeBytes);
    }

    public function test_metadata_dto_detected_extension_is_lowercase(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'test.jpg'));

        self::assertSame(strtolower($metadata->detectedExtension), $metadata->detectedExtension);
    }

    public function test_metadata_dto_detected_mime_is_lowercase(): void
    {
        $path     = TestImageFactory::jpeg();
        $metadata = $this->reader->read($this->makeInput($path, 'test.jpg'));

        self::assertSame(strtolower($metadata->detectedMimeType), $metadata->detectedMimeType);
    }
}
