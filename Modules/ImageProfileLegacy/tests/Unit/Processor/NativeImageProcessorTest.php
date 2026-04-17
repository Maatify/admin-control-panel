<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Processor;

use ImageProfileLegacy\tests\Fixtures\TestImageFactory;
use Maatify\ImageProfileLegacy\DTO\OptimizationOptionsDTO;
use Maatify\ImageProfileLegacy\DTO\ProcessedImageDTO;
use Maatify\ImageProfileLegacy\DTO\ResizeOptionsDTO;
use Maatify\ImageProfileLegacy\Enum\ResizeModeEnum;
use Maatify\ImageProfileLegacy\Exception\ImageProfileException;
use Maatify\ImageProfileLegacy\Processor\NativeImageProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @requires extension gd
 */
#[CoversClass(\Maatify\ImageProfileLegacy\Processor\NativeImageProcessor::class)]
final class NativeImageProcessorTest extends TestCase
{
    private NativeImageProcessor $processor;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->processor = new NativeImageProcessor();
        $this->outputDir = sys_get_temp_dir() . '/maatify_processor_test_' . uniqid('', true);
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
        $this->removeDir($this->outputDir);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDir($file) : unlink($file);
        }
        rmdir($dir);
    }

    // -------------------------------------------------------------------------
    // resize — return type
    // -------------------------------------------------------------------------

    public function test_resize_returns_processed_image_dto(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/resized.jpg';
        $opts   = ResizeOptionsDTO::fit(50, 50);

        $result = $this->processor->resize($source, $target, $opts);

        self::assertInstanceOf(ProcessedImageDTO::class, $result);
    }

    // -------------------------------------------------------------------------
    // resize — Fit mode: output fits within bounding box
    // -------------------------------------------------------------------------

    public function test_resize_fit_produces_file_on_disk(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/fit.jpg';

        $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertFileExists($target);
    }

    public function test_resize_fit_dimensions_do_not_exceed_bounding_box(): void
    {
        $source = TestImageFactory::jpeg(); // 100×100
        $target = $this->outputDir . '/fit_small.jpg';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(40, 40));

        self::assertLessThanOrEqual(40, $result->width);
        self::assertLessThanOrEqual(40, $result->height);
    }

    // -------------------------------------------------------------------------
    // resize — Fill mode: output is exactly the requested size
    // -------------------------------------------------------------------------

    public function test_resize_fill_produces_exact_dimensions(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/fill.jpg';
        $opts   = new ResizeOptionsDTO(60, 40, ResizeModeEnum::Fill);

        $result = $this->processor->resize($source, $target, $opts);

        self::assertSame(60, $result->width);
        self::assertSame(40, $result->height);
    }

    // -------------------------------------------------------------------------
    // resize — Stretch mode: output is exactly the requested size
    // -------------------------------------------------------------------------

    public function test_resize_stretch_produces_exact_dimensions(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/stretch.jpg';
        $opts   = new ResizeOptionsDTO(80, 30, ResizeModeEnum::Stretch);

        $result = $this->processor->resize($source, $target, $opts);

        self::assertSame(80, $result->width);
        self::assertSame(30, $result->height);
    }

    // -------------------------------------------------------------------------
    // resize — format conversion via outputFormat
    // -------------------------------------------------------------------------

    public function test_resize_can_convert_jpeg_to_webp(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/converted.webp';
        $opts   = ResizeOptionsDTO::webpThumbnail(50, 50);

        $result = $this->processor->resize($source, $target, $opts);

        self::assertSame('webp', $result->format);
        self::assertSame('image/webp', $result->mimeType);
        self::assertFileExists($target);
    }

    // -------------------------------------------------------------------------
    // resize — result metadata
    // -------------------------------------------------------------------------

    public function test_resize_result_has_positive_size_bytes(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/sized.jpg';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertGreaterThan(0, $result->sizeBytes);
    }

    public function test_resize_result_records_processing_time(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/timed.jpg';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertGreaterThanOrEqual(0, $result->processingTimeMs);
    }

    public function test_resize_result_output_path_matches_target(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/path_check.jpg';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertSame($target, $result->outputPath);
    }

    // -------------------------------------------------------------------------
    // resize — different source formats
    // -------------------------------------------------------------------------

    public function test_resize_works_with_png_source(): void
    {
        $source = TestImageFactory::png();
        $target = $this->outputDir . '/from_png.png';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertFileExists($target);
        self::assertGreaterThan(0, $result->width);
    }

    public function test_resize_works_with_webp_source(): void
    {
        $source = TestImageFactory::webp();
        $target = $this->outputDir . '/from_webp.webp';

        $result = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));

        self::assertFileExists($target);
        self::assertGreaterThan(0, $result->width);
    }

    // -------------------------------------------------------------------------
    // resize — invalid source path throws
    // -------------------------------------------------------------------------

    public function test_resize_throws_on_missing_source(): void
    {
        $source = '/tmp/no_such_image_' . uniqid('', true) . '.jpg';
        $target = $this->outputDir . '/should_not_exist.jpg';

        $this->expectException(ImageProfileException::class);

        $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));
    }

    public function test_resize_throws_on_non_image_source(): void
    {
        $source = TestImageFactory::notAnImage();
        $target = $this->outputDir . '/should_not_exist.jpg';

        $this->expectException(ImageProfileException::class);

        $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));
    }

    // -------------------------------------------------------------------------
    // optimize — JPEG recompression
    // -------------------------------------------------------------------------

    public function test_optimize_recompress_produces_file(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/optimized.jpg';

        $result = $this->processor->optimize($source, $target, OptimizationOptionsDTO::recompress(70));

        self::assertFileExists($target);
        self::assertGreaterThan(0, $result->sizeBytes);
    }

    public function test_optimize_returns_processed_image_dto(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/opt_dto.jpg';

        $result = $this->processor->optimize($source, $target, OptimizationOptionsDTO::recompress());

        self::assertInstanceOf(ProcessedImageDTO::class, $result);
    }

    // -------------------------------------------------------------------------
    // optimize — format conversion to WebP
    // -------------------------------------------------------------------------

    public function test_optimize_converts_to_webp(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/opt_webp.webp';

        $result = $this->processor->optimize($source, $target, OptimizationOptionsDTO::toWebp(80));

        self::assertSame('webp', $result->format);
        self::assertSame('image/webp', $result->mimeType);
        self::assertFileExists($target);
    }

    // -------------------------------------------------------------------------
    // convertToWebp — convenience method
    // -------------------------------------------------------------------------

    public function test_convert_to_webp_produces_webp_file(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/conv.webp';

        $result = $this->processor->convertToWebp($source, $target);

        self::assertSame('image/webp', $result->mimeType);
        self::assertFileExists($target);
    }

    public function test_convert_to_webp_from_png(): void
    {
        $source = TestImageFactory::png();
        $target = $this->outputDir . '/png_to_webp.webp';

        $result = $this->processor->convertToWebp($source, $target, 75);

        self::assertSame('image/webp', $result->mimeType);
        self::assertGreaterThan(0, $result->sizeBytes);
    }

    // -------------------------------------------------------------------------
    // JSON serialization of result
    // -------------------------------------------------------------------------

    public function test_processed_image_dto_is_json_serializable(): void
    {
        $source = TestImageFactory::jpeg();
        $target = $this->outputDir . '/json.jpg';

        $result  = $this->processor->resize($source, $target, ResizeOptionsDTO::fit(50, 50));
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('outputPath', $decoded);
        self::assertArrayHasKey('width', $decoded);
        self::assertArrayHasKey('height', $decoded);
        self::assertArrayHasKey('sizeBytes', $decoded);
        self::assertArrayHasKey('mimeType', $decoded);
        self::assertArrayHasKey('format', $decoded);
        self::assertArrayHasKey('processingTimeMs', $decoded);
    }
}
