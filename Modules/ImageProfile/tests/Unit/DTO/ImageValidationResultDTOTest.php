<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\DTO;

use Maatify\ImageProfile\DTO\ImageMetadataDTO;
use Maatify\ImageProfile\DTO\ImageValidationErrorCollectionDTO;
use Maatify\ImageProfile\DTO\ImageValidationErrorDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;
use Maatify\ImageProfile\DTO\ImageValidationWarningCollectionDTO;
use Maatify\ImageProfile\Enum\ValidationErrorCodeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\DTO\ImageValidationResultDTO::class)]
final class ImageValidationResultDTOTest extends TestCase
{
    private function makeMetadata(): ImageMetadataDTO
    {
        return new ImageMetadataDTO(
            width:             800,
            height:            600,
            detectedMimeType:  'image/jpeg',
            detectedExtension: 'jpg',
            sizeBytes:         204800,
        );
    }

    private function makeError(): ImageValidationErrorDTO
    {
        return ImageValidationErrorDTO::mimeNotAllowed('image/gif', 'image/jpeg, image/png');
    }

    // -------------------------------------------------------------------------
    // valid() factory
    // -------------------------------------------------------------------------

    public function test_valid_factory_sets_is_valid_true(): void
    {
        $result = ImageValidationResultDTO::valid('my_profile', $this->makeMetadata());

        self::assertTrue($result->isValid());
        self::assertTrue($result->isValid);
    }

    public function test_valid_factory_produces_empty_errors(): void
    {
        $result = ImageValidationResultDTO::valid('my_profile', $this->makeMetadata());

        self::assertTrue($result->errors->isEmpty());
    }

    public function test_valid_factory_produces_empty_warnings_by_default(): void
    {
        $result = ImageValidationResultDTO::valid('my_profile', $this->makeMetadata());

        self::assertTrue($result->warnings->isEmpty());
    }

    public function test_valid_factory_sets_profile_code(): void
    {
        $result = ImageValidationResultDTO::valid('homepage_banner', $this->makeMetadata());

        self::assertSame('homepage_banner', $result->profileCode);
    }

    public function test_valid_factory_sets_metadata(): void
    {
        $meta   = $this->makeMetadata();
        $result = ImageValidationResultDTO::valid('my_profile', $meta);

        self::assertSame($meta, $result->metadata);
    }

    // -------------------------------------------------------------------------
    // invalid() factory
    // -------------------------------------------------------------------------

    public function test_invalid_factory_sets_is_valid_false(): void
    {
        $errors = new ImageValidationErrorCollectionDTO($this->makeError());
        $result = ImageValidationResultDTO::invalid('my_profile', null, $errors);

        self::assertFalse($result->isValid());
        self::assertFalse($result->isValid);
    }

    public function test_invalid_factory_carries_errors(): void
    {
        $error  = $this->makeError();
        $errors = new ImageValidationErrorCollectionDTO($error);
        $result = ImageValidationResultDTO::invalid('my_profile', null, $errors);

        self::assertSame(1, $result->errors->count());
        self::assertTrue($result->errors->hasCode(ValidationErrorCodeEnum::MimeNotAllowed));
    }

    public function test_invalid_factory_accepts_null_metadata(): void
    {
        $errors = new ImageValidationErrorCollectionDTO($this->makeError());
        $result = ImageValidationResultDTO::invalid('my_profile', null, $errors);

        self::assertNull($result->metadata);
    }

    public function test_invalid_factory_accepts_non_null_metadata(): void
    {
        $meta   = $this->makeMetadata();
        $error  = $this->makeError();
        $errors = new ImageValidationErrorCollectionDTO($error);
        $result = ImageValidationResultDTO::invalid('my_profile', $meta, $errors);

        self::assertSame($meta, $result->metadata);
    }

    // -------------------------------------------------------------------------
    // Invariant: valid => empty errors
    // -------------------------------------------------------------------------

    public function test_contract_invariant_valid_result_has_empty_errors(): void
    {
        $result = ImageValidationResultDTO::valid('my_profile', $this->makeMetadata());

        self::assertTrue($result->isValid);
        self::assertTrue($result->errors->isEmpty());
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function test_json_serialize_contains_all_keys(): void
    {
        $result = ImageValidationResultDTO::valid('banner', $this->makeMetadata());
        $data   = $result->jsonSerialize();

        self::assertArrayHasKey('isValid', $data);
        self::assertArrayHasKey('profileCode', $data);
        self::assertArrayHasKey('metadata', $data);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('warnings', $data);
    }

    public function test_valid_result_json_encode_is_valid_true(): void
    {
        $result = ImageValidationResultDTO::valid('banner', $this->makeMetadata());

        $decoded = json_decode((string) json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var array{
         *     isValid: bool,
         *     profileCode: string,
         *     metadata: array<string, mixed>|null,
         *     errors: list<array<string, mixed>>,
         *     warnings: list<array<string, mixed>>
         * } $decoded
         */
        self::assertTrue($decoded['isValid']);
        self::assertSame('banner', $decoded['profileCode']);
        self::assertSame([], $decoded['errors']);
    }

    public function test_invalid_result_json_encode_is_valid_false(): void
    {
        $errors = new ImageValidationErrorCollectionDTO($this->makeError());
        $result = ImageValidationResultDTO::invalid('banner', null, $errors);

        $decoded = json_decode((string) json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var array{
         *     isValid: bool,
         *     profileCode: string,
         *     metadata: array<string, mixed>|null,
         *     errors: list<array<string, mixed>>,
         *     warnings: list<array<string, mixed>>
         * } $decoded
         */
        self::assertFalse($decoded['isValid']);
        self::assertCount(1, $decoded['errors']);
    }
}
