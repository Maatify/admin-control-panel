<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Validators;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Validators\ImageDimensionsValidator;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for ImageDimensionsValidator
 */
final class ImageDimensionsValidatorTest extends StorageModuleTestCase
{
    /**
     * Create a minimal valid JPEG image for testing.
     *
     * Returns raw JPEG binary data with specific dimensions.
     *
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     *
     * @return string Raw JPEG data
     */
    private function createJpegImage(int $width, int $height): string
    {
        // Create a simple image using GD
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new \RuntimeException('Failed to create image');
        }

        // Fill with color
        $color = imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            throw new \RuntimeException('Failed to allocate color');
        }
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);

        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        return $jpeg;
    }

    public function testValidatesImageWithinDimensions(): void
    {
        $constraints = new ImageDimensions(
            minWidth: 100,
            minHeight: 100,
            maxWidth: 1000,
            maxHeight: 1000,
        );

        $validator = new ImageDimensionsValidator($constraints);

        // 500x500 should pass
        $jpeg = $this->createJpegImage(500, 500);
        $file = $this->createMockUploadedFile($jpeg, 'image.jpg');

        $validator->validate($file);
        $this->assertTrue(true);
    }

    public function testRejectsImageTooSmall(): void
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('dimensions');

        $constraints = new ImageDimensions(
            minWidth: 500,
            minHeight: 500,
            maxWidth: 1000,
            maxHeight: 1000,
        );

        $validator = new ImageDimensionsValidator($constraints);

        // 100x100 is too small
        $jpeg = $this->createJpegImage(100, 100);
        $file = $this->createMockUploadedFile($jpeg, 'image.jpg');

        $validator->validate($file);
    }

    public function testRejectsImageTooLarge(): void
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('dimensions');

        $constraints = new ImageDimensions(
            minWidth: 100,
            minHeight: 100,
            maxWidth: 500,
            maxHeight: 500,
        );

        $validator = new ImageDimensionsValidator($constraints);

        // 1000x1000 is too large
        $jpeg = $this->createJpegImage(1000, 1000);
        $file = $this->createMockUploadedFile($jpeg, 'image.jpg');

        $validator->validate($file);
    }

    public function testAcceptsExactBoundaries(): void
    {
        $constraints = new ImageDimensions(
            minWidth: 100,
            minHeight: 100,
            maxWidth: 500,
            maxHeight: 500,
        );

        $validator = new ImageDimensionsValidator($constraints);

        // Exact min
        $jpeg = $this->createJpegImage(100, 100);
        $file = $this->createMockUploadedFile($jpeg, 'image.jpg');
        $validator->validate($file);

        // Exact max
        $jpeg = $this->createJpegImage(500, 500);
        $file = $this->createMockUploadedFile($jpeg, 'image.jpg');
        $validator->validate($file);

        $this->assertTrue(true);
    }
}
