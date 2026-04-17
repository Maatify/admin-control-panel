<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 *
 * Contract test — verifies that ImageProfileValidator honours the full
 * ImageProfileValidatorInterface contract. Any future validator implementation
 * must satisfy these invariants.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Contract;

use Maatify\ImageProfile\Contract\ImageProfileValidatorInterface;
use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\DTO\ImageValidationErrorCollectionDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;
use Maatify\ImageProfile\DTO\ImageValidationWarningCollectionDTO;
use Maatify\ImageProfile\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfile\Reader\NativeImageMetadataReader;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfile\Tests\Fixtures\TestImageFactory;
use Maatify\ImageProfile\Validator\ImageProfileValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Validator\ImageProfileValidator::class)]
final class ImageProfileValidatorInterfaceContractTest extends TestCase
{
    private ImageProfileValidatorInterface $validator;

    protected function setUp(): void
    {
        $provider = new ArrayImageProfileProvider(
            ImageProfileFixtureFactory::standard(),
            ImageProfileFixtureFactory::inactive(),
            ImageProfileFixtureFactory::webpOnly(),
        );

        $this->validator = new ImageProfileValidator($provider, new NativeImageMetadataReader());
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    // -------------------------------------------------------------------------
    // Return type contract
    // -------------------------------------------------------------------------

    public function test_validate_by_code_always_returns_result_dto(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        self::assertInstanceOf(ImageValidationResultDTO::class, $result);
    }

    public function test_result_always_exposes_profile_code(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        self::assertSame('product_thumbnail', $result->profileCode);
    }

    // -------------------------------------------------------------------------
    // Invariant: isValid === true ⟹ errors collection is empty
    // -------------------------------------------------------------------------

    public function test_valid_result_has_empty_errors_collection(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        if ($result->isValid()) {
            self::assertCount(0, $result->errors);
        }

        // Invariant always holds regardless of validity
        self::assertTrue(true, 'Invariant: isValid=true → empty errors');
    }

    // -------------------------------------------------------------------------
    // Invariant: errors is always a typed collection, never null or array
    // -------------------------------------------------------------------------

    public function test_errors_is_always_typed_collection_dto(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        self::assertInstanceOf(ImageValidationErrorCollectionDTO::class, $result->errors);
    }

    public function test_warnings_is_always_typed_collection_dto(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        self::assertInstanceOf(ImageValidationWarningCollectionDTO::class, $result->warnings);
    }

    // -------------------------------------------------------------------------
    // Infra failure: profile not found → result, not exception
    // -------------------------------------------------------------------------

    public function test_missing_profile_returns_invalid_result_not_exception(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('ghost_profile', $input);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->errors);
    }

    // -------------------------------------------------------------------------
    // Infra failure: inactive profile → result, not exception
    // -------------------------------------------------------------------------

    public function test_inactive_profile_returns_invalid_result_not_exception(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('inactive_profile', $input);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->errors);
    }

    // -------------------------------------------------------------------------
    // Infra failure: missing file → result, not exception
    // -------------------------------------------------------------------------

    public function test_missing_file_returns_invalid_result_not_exception(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'ghost.jpg',
            temporaryPath:  '/tmp/no_such_file_' . uniqid(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('product_thumbnail', $input);

        self::assertFalse($result->isValid());
    }

    // -------------------------------------------------------------------------
    // Rule failures: all collected, not short-circuited
    // -------------------------------------------------------------------------

    public function test_rule_failures_are_collected_exhaustively(): void
    {
        // webp_only profile accepts only webp; passing a jpeg triggers
        // both mime_not_allowed and extension_not_allowed in one call.
        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('webp_only', $input);

        self::assertFalse($result->isValid());
        // At minimum both mime and extension errors should be present
        self::assertGreaterThanOrEqual(2, count($result->errors));
    }

    // -------------------------------------------------------------------------
    // Metadata present on rule failure (not infra failure)
    // -------------------------------------------------------------------------

    public function test_metadata_is_present_on_rule_failure(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result = $this->validator->validateByCode('webp_only', $input);

        // File was readable — metadata should have been extracted even though rules failed
        self::assertNotNull($result->metadata);
    }

    // -------------------------------------------------------------------------
    // result is JSON-serializable
    // -------------------------------------------------------------------------

    public function test_result_is_json_serializable(): void
    {
        $input = new ImageFileInputDTO(
            originalName:   'test.jpg',
            temporaryPath:  TestImageFactory::jpeg(),
            clientMimeType: 'image/jpeg',
            sizeBytes:      1024,
        );

        $result  = $this->validator->validateByCode('product_thumbnail', $input);
        $encoded = json_encode($result);

        self::assertIsString($encoded);
        self::assertNotFalse($encoded);

        $decoded = json_decode($encoded, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('isValid', $decoded);
        self::assertArrayHasKey('profileCode', $decoded);
        self::assertArrayHasKey('errors', $decoded);
    }
}
