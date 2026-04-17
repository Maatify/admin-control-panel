<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Validator;

use ImageProfileLegacy\tests\Fixtures\ImageProfileFixtureFactory;
use ImageProfileLegacy\tests\Fixtures\TestImageFactory;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\Enum\ValidationErrorCodeEnum;
use Maatify\ImageProfileLegacy\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader;
use Maatify\ImageProfileLegacy\Validator\ImageProfileValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Full rule-matrix tests for the core validator.
 *
 * Each test group exercises exactly one failure mode so that failure messages
 * are unambiguous and test names map directly to error codes.
 *
 */
#[CoversClass(\Maatify\ImageProfileLegacy\Validator\ImageProfileValidator::class)]
final class ImageProfileValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeValidator(ArrayImageProfileProvider $provider): ImageProfileValidator
    {
        return new ImageProfileValidator($provider, new NativeImageMetadataReader());
    }

    private function makeInput(string $path, string $originalName = 'test.jpg'): ImageFileInputDTO
    {
        return new ImageFileInputDTO(
            originalName:   $originalName,
            temporaryPath:  $path,
            clientMimeType: null,
            sizeBytes:      filesize($path) ?: 0,
        );
    }

    // -------------------------------------------------------------------------
    // profile_not_found
    // -------------------------------------------------------------------------

    public function test_profile_not_found_returns_invalid_result(): void
    {
        $provider  = new ArrayImageProfileProvider();
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg();
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('does_not_exist', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::ProfileNotFound));
        self::assertNull($result->metadata);
    }

    // -------------------------------------------------------------------------
    // profile_inactive
    // -------------------------------------------------------------------------

    public function test_profile_inactive_returns_invalid_result(): void
    {
        $profile   = ImageProfileFixtureFactory::inactive();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg();
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('inactive_profile', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::ProfileInactive));
        self::assertNull($result->metadata);
    }

    // -------------------------------------------------------------------------
    // file_not_found
    // -------------------------------------------------------------------------

    public function test_file_not_found_returns_invalid_result(): void
    {
        $profile  = ImageProfileFixtureFactory::unrestricted();
        $provider = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $input = new ImageFileInputDTO(
            originalName:   'ghost.jpg',
            temporaryPath:  '/tmp/this_file_does_not_exist_' . uniqid(),
            clientMimeType: null,
            sizeBytes:      0,
        );

        $result = $validator->validateByCode('unrestricted', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::FileNotFound));
    }

    // -------------------------------------------------------------------------
    // Happy path — valid image passes all rules
    // -------------------------------------------------------------------------

    public function test_valid_jpeg_passes_standard_profile(): void
    {
        $profile   = ImageProfileFixtureFactory::standard();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(400, 400);
        $input  = $this->makeInput($path, 'photo.jpg');
        $result = $validator->validateByCode('standard_profile', $input);

        self::assertTrue($result->isValid());
        self::assertTrue($result->errors->isEmpty());
        self::assertNotNull($result->metadata);
    }

    public function test_valid_png_passes_standard_profile(): void
    {
        $profile   = ImageProfileFixtureFactory::standard();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::png(400, 400);
        $input  = $this->makeInput($path, 'photo.png');
        $result = $validator->validateByCode('standard_profile', $input);

        self::assertTrue($result->isValid());
        self::assertTrue($result->errors->isEmpty());
    }

    public function test_valid_webp_passes_standard_profile(): void
    {
        $profile   = ImageProfileFixtureFactory::standard();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::webp(400, 400);
        $input  = $this->makeInput($path, 'photo.webp');
        $result = $validator->validateByCode('standard_profile', $input);

        self::assertTrue($result->isValid());
        self::assertTrue($result->errors->isEmpty());
    }

    public function test_valid_image_passes_unrestricted_profile(): void
    {
        $provider  = new ArrayImageProfileProvider(ImageProfileFixtureFactory::unrestricted());
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(10, 10);
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('unrestricted', $input);

        self::assertTrue($result->isValid());
    }

    // -------------------------------------------------------------------------
    // mime_not_allowed
    // -------------------------------------------------------------------------

    public function test_mime_not_allowed_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::webpOnly();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        // JPEG file against a WebP-only profile
        $path   = TestImageFactory::jpeg(200, 200);
        $input  = $this->makeInput($path, 'photo.jpg');
        $result = $validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::MimeNotAllowed));
    }

    // -------------------------------------------------------------------------
    // extension_not_allowed
    // -------------------------------------------------------------------------

    public function test_extension_not_allowed_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::webpOnly();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(200, 200);
        $input  = $this->makeInput($path, 'photo.jpg');
        $result = $validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::ExtensionNotAllowed));
    }

    // -------------------------------------------------------------------------
    // width_too_small / height_too_small
    // -------------------------------------------------------------------------

    public function test_width_too_small_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::strictMinDimensions(); // minWidth=1920
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(100, 1080); // width=100 < 1920
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('strict_min_dimensions', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::WidthTooSmall));
    }

    public function test_height_too_small_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::strictMinDimensions(); // minHeight=1080
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(1920, 100); // height=100 < 1080
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('strict_min_dimensions', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::HeightTooSmall));
    }

    // -------------------------------------------------------------------------
    // width_too_large / height_too_large
    // -------------------------------------------------------------------------

    public function test_width_too_large_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::strictMaxDimensions(); // maxWidth=10
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(200, 8); // width=200 > 10
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('strict_max_dimensions', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::WidthTooLarge));
    }

    public function test_height_too_large_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::strictMaxDimensions(); // maxHeight=10
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(8, 200); // height=200 > 10
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('strict_max_dimensions', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::HeightTooLarge));
    }

    // -------------------------------------------------------------------------
    // file_too_large
    // -------------------------------------------------------------------------

    public function test_file_too_large_returns_error(): void
    {
        $profile   = ImageProfileFixtureFactory::strictMaxSize(); // maxSizeBytes=1
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(200, 200);
        $input  = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  $path,
            clientMimeType: null,
            sizeBytes:      filesize($path) ?: 0,
        );
        $result = $validator->validateByCode('strict_max_size', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::FileTooLarge));
    }

    // -------------------------------------------------------------------------
    // Multiple errors collected in one pass
    // -------------------------------------------------------------------------

    public function test_multiple_rule_failures_are_all_collected(): void
    {
        // Profile: minWidth=1920, minHeight=1080, maxSize=1 byte, webpOnly
        // Image: tiny JPEG — will fail mime, extension, width, height, size
        $profile   = ImageProfileFixtureFactory::webpOnly();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(50, 50);
        $input  = $this->makeInput($path, 'tiny.jpg');
        $result = $validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        // At least mime and extension errors must be present
        self::assertGreaterThanOrEqual(2, $result->errors->count());
    }

    // -------------------------------------------------------------------------
    // Metadata is present on rule failures (not on infra failures)
    // -------------------------------------------------------------------------

    public function test_metadata_is_present_when_rule_fails(): void
    {
        $profile   = ImageProfileFixtureFactory::webpOnly();
        $provider  = new ArrayImageProfileProvider($profile);
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg(200, 200);
        $input  = $this->makeInput($path, 'photo.jpg');
        $result = $validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        self::assertNotNull($result->metadata); // metadata was extracted even though rules failed
    }

    public function test_metadata_is_null_when_profile_not_found(): void
    {
        $provider  = new ArrayImageProfileProvider();
        $validator = $this->makeValidator($provider);

        $path   = TestImageFactory::jpeg();
        $input  = $this->makeInput($path);
        $result = $validator->validateByCode('missing', $input);

        self::assertNull($result->metadata); // short-circuited before metadata extraction
    }
}
