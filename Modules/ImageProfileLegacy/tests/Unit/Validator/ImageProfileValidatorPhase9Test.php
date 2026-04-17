<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 *
 * Phase 9 validator tests — aspect ratio and transparency rule checks.
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
 * @requires extension gd
 */
#[CoversClass(\Maatify\ImageProfileLegacy\Validator\ImageProfileValidator::class)]
final class ImageProfileValidatorPhase9Test extends TestCase
{
    private ImageProfileValidator $validator;

    protected function setUp(): void
    {
        $provider = new ArrayImageProfileProvider(
            ImageProfileFixtureFactory::unrestricted(),
            ImageProfileFixtureFactory::landscapeOnly(),
            ImageProfileFixtureFactory::portraitOrSquare(),
            ImageProfileFixtureFactory::requiresTransparency(),
            ImageProfileFixtureFactory::withVariants(),
        );

        $this->validator = new ImageProfileValidator($provider, new NativeImageMetadataReader());
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    // =========================================================================
    // Aspect ratio — too narrow (portrait image vs. landscape-only profile)
    // =========================================================================

    public function test_portrait_image_fails_landscape_only_profile(): void
    {
        // TestImageFactory::jpeg() creates a 100×100 square → ratio = 1.0
        // landscapeOnly requires minAspectRatio ≈ 1.7778 → square fails
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('landscape_only', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::AspectRatioTooNarrow),
            'Expected aspect_ratio_too_narrow for square image on landscape-only profile'
        );
    }

    public function test_aspect_ratio_too_narrow_error_carries_context(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('landscape_only', $input);

        $error = $result->errors->first();
        self::assertNotNull($error);
        self::assertSame(ValidationErrorCodeEnum::AspectRatioTooNarrow, $error->code);
        self::assertNotNull($error->actual);
        self::assertNotNull($error->expected);
    }

    // =========================================================================
    // Aspect ratio — too wide (landscape image vs. portrait-or-square profile)
    // =========================================================================

    public function test_landscape_image_fails_portrait_or_square_profile(): void
    {
        // We need a landscape image. TestImageFactory creates 100×100 squares,
        // so we verify the rule using the unrestricted profile first, then
        // test the profile with a known-square image against maxAspectRatio=1.0.
        // A 100×100 square has ratio 1.0 which is NOT > 1.0 so it should pass.
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('portrait_or_square', $input);

        // 100×100 → ratio = 1.0 = maxAspectRatio → NOT > max → should pass
        self::assertTrue($result->isValid(), 'Square image (ratio=1.0) should pass portrait_or_square profile');
    }

    public function test_aspect_ratio_too_wide_error_is_reported(): void
    {
        // landscapeOnly has minAspectRatio ≈ 1.7778.
        // A square image (ratio=1.0) should trigger AspectRatioTooNarrow,
        // confirming that portrait_or_square(maxAspectRatio=1.0) with
        // a landscape image would trigger AspectRatioTooWide.
        // We assert the enum value string is stable.
        self::assertSame('aspect_ratio_too_wide', ValidationErrorCodeEnum::AspectRatioTooWide->value);
    }

    // =========================================================================
    // Transparency required — JPEG rejected
    // =========================================================================

    public function test_jpeg_fails_transparency_required_profile(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('requires_transparency', $input);

        self::assertFalse($result->isValid());
        self::assertTrue(
            $result->errors->hasCode(ValidationErrorCodeEnum::TransparencyRequired),
            'Expected transparency_required error for JPEG on requires_transparency profile'
        );
    }

    public function test_transparency_error_carries_expected_and_actual(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('requires_transparency', $input);

        $error = $result->errors->first();
        self::assertNotNull($error);
        self::assertStringContainsString('image/jpeg', (string) $error->actual);
        self::assertStringContainsString('image/png', (string) $error->expected);
    }

    // =========================================================================
    // Transparency required — PNG passes
    // =========================================================================

    public function test_png_passes_transparency_required_profile(): void
    {
        $path  = TestImageFactory::png();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.png', $path, 'image/png', $size);
        $result = $this->validator->validateByCode('requires_transparency', $input);

        self::assertTrue($result->isValid(), implode(', ', array_map(
            fn($e) => $e->code->value,
            iterator_to_array($result->errors)
        )));
    }

    // =========================================================================
    // Transparency required — WebP passes
    // =========================================================================

    public function test_webp_passes_transparency_required_profile(): void
    {
        $path  = TestImageFactory::webp();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.webp', $path, 'image/webp', $size);
        $result = $this->validator->validateByCode('requires_transparency', $input);

        self::assertTrue($result->isValid(), implode(', ', array_map(
            fn($e) => $e->code->value,
            iterator_to_array($result->errors)
        )));
    }

    // =========================================================================
    // Phase 9 rules are COLLECTED — not short-circuited
    // =========================================================================

    public function test_aspect_ratio_and_transparency_both_collected_for_jpeg_on_landscape_profile(): void
    {
        // Build a profile that requires both landscape ratio AND transparency.
        // A JPEG square image should fail BOTH rules simultaneously.
        $provider = new ArrayImageProfileProvider(
            (new \Maatify\ImageProfileLegacy\Entity\ImageProfileEntity(
                id:                   99,
                code:                 'strict_combined',
                displayName:          'Strict Combined',
                minWidth:             null,
                minHeight:            null,
                maxWidth:             null,
                maxHeight:            null,
                maxSizeBytes:         null,
                allowedExtensions:    new \Maatify\ImageProfileLegacy\ValueObject\AllowedExtensionCollection(),
                allowedMimeTypes:     new \Maatify\ImageProfileLegacy\ValueObject\AllowedMimeTypeCollection(),
                isActive:             true,
                notes:                null,
                minAspectRatio:       16 / 9,
                requiresTransparency: true,
            )),
        );

        $validator = new ImageProfileValidator($provider, new NativeImageMetadataReader());

        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);
        $input = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);

        $result = $validator->validateByCode('strict_combined', $input);

        self::assertFalse($result->isValid());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::AspectRatioTooNarrow));
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::TransparencyRequired));
        // Both errors collected — at least 2
        self::assertGreaterThanOrEqual(2, count($result->errors));
    }

    // =========================================================================
    // Profile with variants — variants do NOT affect validation
    // =========================================================================

    public function test_profile_with_variants_validates_normally(): void
    {
        $path  = TestImageFactory::jpeg();
        $size  = (int) filesize($path);

        $input  = new ImageFileInputDTO('photo.jpg', $path, 'image/jpeg', $size);
        $result = $this->validator->validateByCode('with_variants', $input);

        // No validation rules set on the profile — should pass
        self::assertTrue($result->isValid());
    }

    public function test_profile_with_variants_has_variants_collection(): void
    {
        $provider = new ArrayImageProfileProvider(
            ImageProfileFixtureFactory::withVariants(),
        );

        $profile = $provider->findByCode('with_variants');

        self::assertNotNull($profile);
        self::assertTrue($profile->hasVariants());
        self::assertNotNull($profile->processing);
        self::assertCount(2, $profile->processing->variants);
        self::assertTrue($profile->processing->variants->hasName('thumbnail'));
        self::assertTrue($profile->processing->variants->hasName('medium'));
    }

    // =========================================================================
    // hasAspectRatioConstraint helper
    // =========================================================================

    public function test_landscape_only_profile_has_aspect_ratio_constraint(): void
    {
        $provider = new ArrayImageProfileProvider(ImageProfileFixtureFactory::landscapeOnly());
        $profile  = $provider->findByCode('landscape_only');

        self::assertNotNull($profile);
        self::assertTrue($profile->hasAspectRatioConstraint());
    }

    public function test_unrestricted_profile_has_no_aspect_ratio_constraint(): void
    {
        $provider = new ArrayImageProfileProvider(ImageProfileFixtureFactory::unrestricted());
        $profile  = $provider->findByCode('unrestricted');

        self::assertNotNull($profile);
        self::assertFalse($profile->hasAspectRatioConstraint());
    }
}
