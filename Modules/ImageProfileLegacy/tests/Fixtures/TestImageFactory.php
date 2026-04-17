<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Fixtures;

use RuntimeException;

/**
 * Creates real image files in the system temp directory for use in tests.
 *
 * Requires the GD extension (`ext-gd`).
 *
 * All created files are registered for cleanup. Call `cleanup()` (or use
 * the TestCase helper) to delete them after each test.
 *
 * Image format support:
 *   - JPEG  — imagejpeg()
 *   - PNG   — imagepng()
 *   - WebP  — imagewebp()  (requires GD with WebP support)
 *   - GIF   — imagegif()
 */
final class TestImageFactory
{
    /**
     * @var list<string>
     */
    private static array $tempFiles = [];

    /**
     * Create a real JPEG file of the given dimensions.
     *
     * @return string Absolute path to the temporary file
     */
    public static function jpeg(int $width = 200, int $height = 200): string
    {
        self::requireGd();

        $path = self::tempPath('jpg');
        $img  = imagecreatetruecolor($width, $height);

        if ($img === false) {
            throw new RuntimeException('imagecreatetruecolor() failed');
        }

        // Fill with a solid colour so the file is valid
        $colour = imagecolorallocate($img, 100, 149, 237); // cornflower blue
        imagefill($img, 0, 0, $colour ?: 0);

        if (! imagejpeg($img, $path, 85)) {
            throw new RuntimeException("imagejpeg() failed writing to {$path}");
        }

        imagedestroy($img);
        self::$tempFiles[] = $path;

        return $path;
    }

    /**
     * Create a real PNG file of the given dimensions.
     *
     * @return string Absolute path to the temporary file
     */
    public static function png(int $width = 200, int $height = 200): string
    {
        self::requireGd();

        $path = self::tempPath('png');
        $img  = imagecreatetruecolor($width, $height);

        if ($img === false) {
            throw new RuntimeException('imagecreatetruecolor() failed');
        }

        $colour = imagecolorallocate($img, 60, 179, 113); // medium sea green
        imagefill($img, 0, 0, $colour ?: 0);

        if (! imagepng($img, $path, 6)) {
            throw new RuntimeException("imagepng() failed writing to {$path}");
        }

        imagedestroy($img);
        self::$tempFiles[] = $path;

        return $path;
    }

    /**
     * Create a real WebP file of the given dimensions.
     *
     * @return string Absolute path to the temporary file
     */
    public static function webp(int $width = 200, int $height = 200): string
    {
        self::requireGd();

        if (! function_exists('imagewebp')) {
            throw new RuntimeException('GD was compiled without WebP support');
        }

        $path = self::tempPath('webp');
        $img  = imagecreatetruecolor($width, $height);

        if ($img === false) {
            throw new RuntimeException('imagecreatetruecolor() failed');
        }

        $colour = imagecolorallocate($img, 255, 140, 0); // dark orange
        imagefill($img, 0, 0, $colour ?: 0);

        if (! imagewebp($img, $path, 80)) {
            throw new RuntimeException("imagewebp() failed writing to {$path}");
        }

        imagedestroy($img);
        self::$tempFiles[] = $path;

        return $path;
    }

    /**
     * Create a real GIF file of the given dimensions.
     *
     * @return string Absolute path to the temporary file
     */
    public static function gif(int $width = 200, int $height = 200): string
    {
        self::requireGd();

        $path = self::tempPath('gif');
        $img  = imagecreate($width, $height);

        if ($img === false) {
            throw new RuntimeException('imagecreate() failed');
        }

        imagecolorallocate($img, 218, 112, 214); // orchid

        if (! imagegif($img, $path)) {
            throw new RuntimeException("imagegif() failed writing to {$path}");
        }

        imagedestroy($img);
        self::$tempFiles[] = $path;

        return $path;
    }

    /**
     * Create a plain text file that is NOT a valid image.
     * Used to test metadata-unreadable and invalid-input scenarios.
     *
     * @return string Absolute path to the temporary file
     */
    public static function notAnImage(): string
    {
        $path    = self::tempPath('txt');
        $written = file_put_contents($path, 'This is not an image file. ' . str_repeat('x', 100));

        if ($written === false) {
            throw new RuntimeException("file_put_contents() failed writing to {$path}");
        }

        self::$tempFiles[] = $path;

        return $path;
    }

    /**
     * Delete all temporary files created during this test run.
     */
    public static function cleanup(): void
    {
        foreach (self::$tempFiles as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        self::$tempFiles = [];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function tempPath(string $extension): string
    {
        return sys_get_temp_dir() . '/maatify_image_test_' . uniqid('', true) . '.' . $extension;
    }

    private static function requireGd(): void
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required to create test images');
        }
    }
}
