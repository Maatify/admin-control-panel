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
use Maatify\ImageProfileLegacy\DTO\GeneratedVariantCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ResizeOptionsDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionDTO;
use Maatify\ImageProfileLegacy\Exception\ImageProfileException;
use Maatify\ImageProfileLegacy\Processor\NativeImageProcessor;
use Maatify\ImageProfileLegacy\Processor\NativeImageVariantGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @requires extension gd
 */
#[CoversClass(\Maatify\ImageProfileLegacy\Processor\NativeImageProcessor::class)]
#[CoversClass(\Maatify\ImageProfileLegacy\Processor\NativeImageVariantGenerator::class)]
final class NativeImageVariantGeneratorTest extends TestCase
{
    private NativeImageVariantGenerator $generator;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->generator = new NativeImageVariantGenerator(new NativeImageProcessor());
        $this->outputDir = sys_get_temp_dir() . '/maatify_variant_test_' . uniqid('', true);
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
    // Return type
    // -------------------------------------------------------------------------

    public function test_generate_returns_generated_variant_collection_dto(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertInstanceOf(GeneratedVariantCollectionDTO::class, $result);
    }

    // -------------------------------------------------------------------------
    // Single variant
    // -------------------------------------------------------------------------

    public function test_generate_single_variant_produces_one_file(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(80, 80),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertCount(1, $result);
    }

    public function test_generate_single_variant_name_is_preserved(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertTrue($result->hasName('thumbnail'));
    }

    public function test_generate_single_variant_file_exists_on_disk(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            new VariantDefinitionDTO('thumb', ResizeOptionsDTO::fit(60, 60)),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        $variant = $result->findByName('thumb');
        self::assertNotNull($variant);
        self::assertFileExists($variant->result->outputPath);
    }

    // -------------------------------------------------------------------------
    // Multiple variants
    // -------------------------------------------------------------------------

    public function test_generate_multiple_variants_all_produced(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(60, 60),
            VariantDefinitionDTO::medium(400, 300),
            VariantDefinitionDTO::large(800, 600),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertCount(3, $result);
        self::assertTrue($result->hasName('thumbnail'));
        self::assertTrue($result->hasName('medium'));
        self::assertTrue($result->hasName('large'));
    }

    public function test_generate_all_variant_files_exist_on_disk(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(60, 60),
            VariantDefinitionDTO::medium(400, 300),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        foreach ($result as $variant) {
            self::assertFileExists(
                $variant->result->outputPath,
                "Variant '{$variant->name}' file not found on disk"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Empty collection
    // -------------------------------------------------------------------------

    public function test_generate_with_empty_collection_returns_empty_result(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = VariantDefinitionCollectionDTO::empty();

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // totalSizeBytes helper
    // -------------------------------------------------------------------------

    public function test_generated_collection_total_size_bytes_is_positive(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(60, 60),
            VariantDefinitionDTO::medium(400, 300),
        );

        $result = $this->generator->generate($source, $this->outputDir, $variants);

        self::assertGreaterThan(0, $result->totalSizeBytes());
    }

    // -------------------------------------------------------------------------
    // Non-existent directory throws
    // -------------------------------------------------------------------------

    public function test_generate_throws_when_target_directory_does_not_exist(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(),
        );

        $this->expectException(ImageProfileException::class);

        $this->generator->generate($source, '/tmp/definitely_does_not_exist_' . uniqid(), $variants);
    }

    // -------------------------------------------------------------------------
    // WebP variant
    // -------------------------------------------------------------------------

    public function test_generate_webp_thumbnail_variant(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            new VariantDefinitionDTO('thumb_webp', ResizeOptionsDTO::webpThumbnail(80, 80)),
        );

        $result  = $this->generator->generate($source, $this->outputDir, $variants);
        $variant = $result->findByName('thumb_webp');

        self::assertNotNull($variant);
        self::assertSame('image/webp', $variant->result->mimeType);
        self::assertFileExists($variant->result->outputPath);
    }

    // -------------------------------------------------------------------------
    // JSON serialization
    // -------------------------------------------------------------------------

    public function test_generated_collection_is_json_serializable(): void
    {
        $source   = TestImageFactory::jpeg();
        $variants = new VariantDefinitionCollectionDTO(
            VariantDefinitionDTO::thumbnail(60, 60),
        );

        $result  = $this->generator->generate($source, $this->outputDir, $variants);
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var list<array{
         *     name: string,
         *     result: array<string, mixed>
         * }> $decoded
         */
        self::assertCount(1, $decoded);

        $first = $decoded[0];

        self::assertArrayHasKey('name', $first);
        self::assertArrayHasKey('result', $first);
    }
}
