<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 *
 * Full-stack integration test — exercises the complete path:
 *
 *   SQLite-seeded PDO provider
 *     → NativeImageMetadataReader (real GD images on disk)
 *       → ImageProfileValidator
 *         → ImageValidationResultDTO
 *
 * No mocks. No ArrayImageProfileProvider. The real PDO path is exercised
 * end-to-end with an in-memory SQLite database seeded with fixtures.
 *
 * Requires: ext-pdo_sqlite, ext-gd, ext-fileinfo
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Integration\Validator;

use ImageProfileLegacy\tests\Fixtures\TestImageFactory;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO;
use Maatify\ImageProfileLegacy\Enum\ValidationErrorCodeEnum;
use Maatify\ImageProfileLegacy\Infrastructure\Persistence\PDO\PdoImageProfileProvider;
use Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader;
use Maatify\ImageProfileLegacy\Validator\ImageProfileValidator;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfileLegacy\Infrastructure\Persistence\PDO\PdoImageProfileProvider::class)]
#[CoversClass(\Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader::class)]
#[CoversClass(\Maatify\ImageProfileLegacy\Validator\ImageProfileValidator::class)]
final class ImageProfileValidatorIntegrationTest extends TestCase
{
    private PDO $pdo;
    private ImageProfileValidator $validator;

    // -------------------------------------------------------------------------
    // Setup / teardown
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema();
        $this->seedProfiles();

        $provider      = new PdoImageProfileProvider($this->pdo);
        $reader        = new NativeImageMetadataReader();
        $this->validator = new ImageProfileValidator($provider, $reader);
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    // -------------------------------------------------------------------------
    // Schema + seed helpers
    // -------------------------------------------------------------------------

    private function createSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE image_profiles (
                id                 INTEGER PRIMARY KEY AUTOINCREMENT,
                code               TEXT NOT NULL UNIQUE,
                display_name       TEXT,
                min_width          INTEGER,
                min_height         INTEGER,
                max_width          INTEGER,
                max_height         INTEGER,
                max_size_bytes     INTEGER,
                allowed_extensions TEXT,
                allowed_mime_types TEXT,
                is_active          INTEGER NOT NULL DEFAULT 1,
                notes              TEXT,
                created_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    private function seedProfiles(): void
    {
        $rows = [
            // Permissive profile — accepts any image up to 5 MB, no type/dim limits
            [
                'code'               => 'permissive',
                'display_name'       => 'Permissive',
                'min_width'          => null,
                'min_height'         => null,
                'max_width'          => null,
                'max_height'         => null,
                'max_size_bytes'     => 5 * 1024 * 1024,
                'allowed_extensions' => null,
                'allowed_mime_types' => null,
                'is_active'          => 1,
                'notes'              => null,
            ],
            // Strict dimensions — min 1920×1080, max 4096×4096
            [
                'code'               => 'banner',
                'display_name'       => 'Banner',
                'min_width'          => 1920,
                'min_height'         => 1080,
                'max_width'          => 4096,
                'max_height'         => 4096,
                'max_size_bytes'     => 10 * 1024 * 1024,
                'allowed_extensions' => 'jpg,jpeg,png',
                'allowed_mime_types' => 'image/jpeg,image/png',
                'is_active'          => 1,
                'notes'              => null,
            ],
            // WebP only
            [
                'code'               => 'webp_only',
                'display_name'       => 'WebP Only',
                'min_width'          => null,
                'min_height'         => null,
                'max_width'          => null,
                'max_height'         => null,
                'max_size_bytes'     => 2 * 1024 * 1024,
                'allowed_extensions' => 'webp',
                'allowed_mime_types' => 'image/webp',
                'is_active'          => 1,
                'notes'              => null,
            ],
            // 1-byte max size — always fails size check
            [
                'code'               => 'tiny_limit',
                'display_name'       => 'Tiny Limit',
                'min_width'          => null,
                'min_height'         => null,
                'max_width'          => null,
                'max_height'         => null,
                'max_size_bytes'     => 1,
                'allowed_extensions' => null,
                'allowed_mime_types' => null,
                'is_active'          => 1,
                'notes'              => null,
            ],
            // Inactive — should always fail with profile_inactive
            [
                'code'               => 'retired',
                'display_name'       => 'Retired',
                'min_width'          => null,
                'min_height'         => null,
                'max_width'          => null,
                'max_height'         => null,
                'max_size_bytes'     => null,
                'allowed_extensions' => null,
                'allowed_mime_types' => null,
                'is_active'          => 0,
                'notes'              => null,
            ],
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO image_profiles
                (code, display_name, min_width, min_height, max_width, max_height,
                 max_size_bytes, allowed_extensions, allowed_mime_types, is_active, notes)
             VALUES
                (:code, :display_name, :min_width, :min_height, :max_width, :max_height,
                 :max_size_bytes, :allowed_extensions, :allowed_mime_types, :is_active, :notes)"
        );

        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    // -------------------------------------------------------------------------
    // Happy path — valid JPEG against permissive profile
    // -------------------------------------------------------------------------

    public function test_valid_jpeg_passes_permissive_profile(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('permissive', $input);

        self::assertInstanceOf(ImageValidationResultDTO::class, $result);
        self::assertTrue($result->isValid(), implode(', ', array_map(
            fn($e) => $e->code->value . ': ' . $e->message,
            iterator_to_array($result->errors)
        )));
    }

    // -------------------------------------------------------------------------
    // Happy path — valid PNG against permissive profile
    // -------------------------------------------------------------------------

    public function test_valid_png_passes_permissive_profile(): void
    {
        $path  = TestImageFactory::png();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.png',
            temporaryPath:  $path,
            clientMimeType: 'image/png',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('permissive', $input);

        self::assertTrue($result->isValid());
    }

    // -------------------------------------------------------------------------
    // Happy path — valid WebP against webp_only profile
    // -------------------------------------------------------------------------

    public function test_valid_webp_passes_webp_only_profile(): void
    {
        $path  = TestImageFactory::webp();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'image.webp',
            temporaryPath:  $path,
            clientMimeType: 'image/webp',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('webp_only', $input);

        self::assertTrue($result->isValid(), implode(', ', array_map(
            fn($e) => $e->code->value . ': ' . $e->message,
            iterator_to_array($result->errors)
        )));
    }

    // -------------------------------------------------------------------------
    // MIME + extension mismatch against webp_only — two errors collected
    // -------------------------------------------------------------------------

    public function test_jpeg_fails_webp_only_profile_with_two_errors(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::MimeNotAllowed),
            'Expected mime_not_allowed error'
        );
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::ExtensionNotAllowed),
            'Expected extension_not_allowed error'
        );
    }

    // -------------------------------------------------------------------------
    // File size violation
    // -------------------------------------------------------------------------

    public function test_file_exceeding_size_limit_fails(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'big.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('tiny_limit', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::FileTooLarge));
    }

    // -------------------------------------------------------------------------
    // Dimension violations — small image fails banner (min 1920×1080)
    // -------------------------------------------------------------------------

    public function test_small_image_fails_banner_min_dimension_checks(): void
    {
        // TestImageFactory creates small images (e.g. 100×100) — well under 1920×1080
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'small.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('banner', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::WidthTooSmall)
            || $result->errors->hasCode(ValidationErrorCodeEnum::HeightTooSmall),
            'Expected at least one dimension error for image smaller than 1920×1080'
        );
    }

    // -------------------------------------------------------------------------
    // Inactive profile → profile_inactive error, short-circuit
    // -------------------------------------------------------------------------

    public function test_inactive_profile_returns_profile_inactive_error(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('retired', $input);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->errors);
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::ProfileInactive));
    }

    // -------------------------------------------------------------------------
    // Unknown profile → profile_not_found error, short-circuit
    // -------------------------------------------------------------------------

    public function test_unknown_profile_returns_profile_not_found_error(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('ghost_profile', $input);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->errors);
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::ProfileNotFound));
    }

    // -------------------------------------------------------------------------
    // Missing file → file_not_found error, short-circuit
    // -------------------------------------------------------------------------

    public function test_missing_file_returns_file_not_found_error(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'ghost.jpg',
            temporaryPath:  '/tmp/no_such_file_' . uniqid('', true),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('permissive', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::FileNotFound)
            || $result->errors->hasCode(ValidationErrorCodeEnum::FileNotReadable)
            || $result->errors->hasCode(ValidationErrorCodeEnum::MetadataUnreadable)
        );
    }

    // -------------------------------------------------------------------------
    // Not-an-image file → metadata_unreadable error
    // -------------------------------------------------------------------------

    public function test_non_image_file_returns_metadata_unreadable_error(): void
    {
        $path  = TestImageFactory::notAnImage();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'data.txt',
            temporaryPath:  $path,
            clientMimeType: 'text/plain',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result = $this->validator->validateByCode('permissive', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::MetadataUnreadable)
            || $result->errors->hasCode(ValidationErrorCodeEnum::MimeNotAllowed)
        );
    }

    // -------------------------------------------------------------------------
    // Metadata is populated on a rule failure (not an infra failure)
    // -------------------------------------------------------------------------

    public function test_metadata_is_populated_on_rule_failure(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        // tiny_limit triggers a rule failure (file_too_large) — not an infra failure
        $result = $this->validator->validateByCode('tiny_limit', $input);

        self::assertFalse($result->isValid());
        self::assertNotNull($result->metadata, 'Metadata should be extracted even when rules fail');
        self::assertGreaterThan(0, $result->metadata->width);
        self::assertGreaterThan(0, $result->metadata->height);
    }

    // -------------------------------------------------------------------------
    // Result is JSON-serializable (full PDO path)
    // -------------------------------------------------------------------------

    public function test_result_is_json_serializable(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = filesize($path);

        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: 'image/jpeg',
            sizeBytes:      $size !== false ? $size : 0,
        );

        $result  = $this->validator->validateByCode('permissive', $input);
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('isValid', $decoded);
        self::assertArrayHasKey('profileCode', $decoded);
        self::assertArrayHasKey('errors', $decoded);
        self::assertArrayHasKey('warnings', $decoded);
        self::assertSame('permissive', $decoded['profileCode']);
    }

    // -------------------------------------------------------------------------
    // Profile code is preserved in the result
    // -------------------------------------------------------------------------

    public function test_profile_code_is_preserved_in_result(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('webp_only', $input);

        self::assertSame('webp_only', $result->profileCode);
    }
}
