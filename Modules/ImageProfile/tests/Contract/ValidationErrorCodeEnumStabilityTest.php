<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 *
 * Contract stability test — ValidationErrorCodeEnum string values MUST NOT
 * change between minor/patch versions. Any change here is a BREAKING CHANGE
 * requiring a major version bump.
 *
 * If a case needs to be added, add a new assertion below.
 * If a case needs to be renamed, bump the major version and update the README.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Contract;

use Maatify\ImageProfile\Enum\ValidationErrorCodeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Enum\ValidationErrorCodeEnum::class)]
final class ValidationErrorCodeEnumStabilityTest extends TestCase
{
    // -------------------------------------------------------------------------
    // String value stability — one assertion per case
    // -------------------------------------------------------------------------

    public function test_profile_not_found_value_is_stable(): void
    {
        self::assertSame('profile_not_found', ValidationErrorCodeEnum::ProfileNotFound->value);
    }

    public function test_profile_inactive_value_is_stable(): void
    {
        self::assertSame('profile_inactive', ValidationErrorCodeEnum::ProfileInactive->value);
    }

    public function test_file_not_found_value_is_stable(): void
    {
        self::assertSame('file_not_found', ValidationErrorCodeEnum::FileNotFound->value);
    }

    public function test_file_not_readable_value_is_stable(): void
    {
        self::assertSame('file_not_readable', ValidationErrorCodeEnum::FileNotReadable->value);
    }

    public function test_metadata_unreadable_value_is_stable(): void
    {
        self::assertSame('metadata_unreadable', ValidationErrorCodeEnum::MetadataUnreadable->value);
    }

    public function test_mime_not_allowed_value_is_stable(): void
    {
        self::assertSame('mime_not_allowed', ValidationErrorCodeEnum::MimeNotAllowed->value);
    }

    public function test_extension_not_allowed_value_is_stable(): void
    {
        self::assertSame('extension_not_allowed', ValidationErrorCodeEnum::ExtensionNotAllowed->value);
    }

    public function test_width_too_small_value_is_stable(): void
    {
        self::assertSame('width_too_small', ValidationErrorCodeEnum::WidthTooSmall->value);
    }

    public function test_height_too_small_value_is_stable(): void
    {
        self::assertSame('height_too_small', ValidationErrorCodeEnum::HeightTooSmall->value);
    }

    public function test_width_too_large_value_is_stable(): void
    {
        self::assertSame('width_too_large', ValidationErrorCodeEnum::WidthTooLarge->value);
    }

    public function test_height_too_large_value_is_stable(): void
    {
        self::assertSame('height_too_large', ValidationErrorCodeEnum::HeightTooLarge->value);
    }

    public function test_file_too_large_value_is_stable(): void
    {
        self::assertSame('file_too_large', ValidationErrorCodeEnum::FileTooLarge->value);
    }

    // Phase 9 additions

    public function test_aspect_ratio_too_narrow_value_is_stable(): void
    {
        self::assertSame('aspect_ratio_too_narrow', ValidationErrorCodeEnum::AspectRatioTooNarrow->value);
    }

    public function test_aspect_ratio_too_wide_value_is_stable(): void
    {
        self::assertSame('aspect_ratio_too_wide', ValidationErrorCodeEnum::AspectRatioTooWide->value);
    }

    public function test_transparency_required_value_is_stable(): void
    {
        self::assertSame('transparency_required', ValidationErrorCodeEnum::TransparencyRequired->value);
    }

    // -------------------------------------------------------------------------
    // Case count — catch accidental additions or removals
    // -------------------------------------------------------------------------

    public function test_enum_has_exactly_fifteen_cases(): void
    {
        self::assertCount(15, ValidationErrorCodeEnum::cases());
    }

    // -------------------------------------------------------------------------
    // from() round-trip — ensure string ↔ enum resolves correctly
    // -------------------------------------------------------------------------

    #[DataProvider('allCodesProvider')]
    public function test_from_string_resolves_correctly(string $value, ValidationErrorCodeEnum $expected): void
    {
        self::assertSame($expected, ValidationErrorCodeEnum::from($value));
    }

    /**
     * @return array<string, array{string, ValidationErrorCodeEnum}>
     */
    public static function allCodesProvider(): array
    {
        return [
            'profile_not_found'   => ['profile_not_found',   ValidationErrorCodeEnum::ProfileNotFound],
            'profile_inactive'    => ['profile_inactive',    ValidationErrorCodeEnum::ProfileInactive],
            'file_not_found'      => ['file_not_found',      ValidationErrorCodeEnum::FileNotFound],
            'file_not_readable'   => ['file_not_readable',   ValidationErrorCodeEnum::FileNotReadable],
            'metadata_unreadable' => ['metadata_unreadable', ValidationErrorCodeEnum::MetadataUnreadable],
            'mime_not_allowed'    => ['mime_not_allowed',    ValidationErrorCodeEnum::MimeNotAllowed],
            'extension_not_allowed' => ['extension_not_allowed', ValidationErrorCodeEnum::ExtensionNotAllowed],
            'width_too_small'     => ['width_too_small',     ValidationErrorCodeEnum::WidthTooSmall],
            'height_too_small'    => ['height_too_small',    ValidationErrorCodeEnum::HeightTooSmall],
            'width_too_large'     => ['width_too_large',     ValidationErrorCodeEnum::WidthTooLarge],
            'height_too_large'    => ['height_too_large',    ValidationErrorCodeEnum::HeightTooLarge],
            'file_too_large'           => ['file_too_large',           ValidationErrorCodeEnum::FileTooLarge],
            'aspect_ratio_too_narrow'  => ['aspect_ratio_too_narrow',  ValidationErrorCodeEnum::AspectRatioTooNarrow],
            'aspect_ratio_too_wide'    => ['aspect_ratio_too_wide',    ValidationErrorCodeEnum::AspectRatioTooWide],
            'transparency_required'    => ['transparency_required',    ValidationErrorCodeEnum::TransparencyRequired],
        ];
    }
}
