<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\DTO;

use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\Exception\InvalidImageInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\DTO\ImageFileInputDTO::class)]
final class ImageFileInputDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Valid construction
    // -------------------------------------------------------------------------

    public function test_constructs_with_valid_data(): void
    {
        $dto = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '/tmp/phpXYZ123',
            clientMimeType: 'image/jpeg',
            sizeBytes:      204800,
        );

        self::assertSame('photo.jpg', $dto->originalName);
        self::assertSame('/tmp/phpXYZ123', $dto->temporaryPath);
        self::assertSame('image/jpeg', $dto->clientMimeType);
        self::assertSame(204800, $dto->sizeBytes);
    }

    public function test_accepts_null_client_mime_type(): void
    {
        $dto = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '/tmp/phpXYZ',
            clientMimeType: null,
            sizeBytes:      1024,
        );

        self::assertNull($dto->clientMimeType);
    }

    public function test_accepts_zero_size_bytes(): void
    {
        $dto = new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '/tmp/phpXYZ',
            clientMimeType: null,
            sizeBytes:      0,
        );

        self::assertSame(0, $dto->sizeBytes);
    }

    // -------------------------------------------------------------------------
    // Guard clauses — must throw InvalidImageInputException
    // -------------------------------------------------------------------------

    public function test_throws_on_empty_original_name(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageFileInputDTO(
            originalName:   '',
            temporaryPath:  '/tmp/phpXYZ',
            clientMimeType: null,
            sizeBytes:      1024,
        );
    }

    public function test_throws_on_whitespace_only_original_name(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageFileInputDTO(
            originalName:   '   ',
            temporaryPath:  '/tmp/phpXYZ',
            clientMimeType: null,
            sizeBytes:      1024,
        );
    }

    public function test_throws_on_empty_temporary_path(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '',
            clientMimeType: null,
            sizeBytes:      1024,
        );
    }

    public function test_throws_on_whitespace_only_temporary_path(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '   ',
            clientMimeType: null,
            sizeBytes:      1024,
        );
    }

    public function test_throws_on_negative_size_bytes(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageFileInputDTO(
            originalName:   'photo.jpg',
            temporaryPath:  '/tmp/phpXYZ',
            clientMimeType: null,
            sizeBytes:      -1,
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function test_json_serialize_contains_all_fields(): void
    {
        $dto = new ImageFileInputDTO(
            originalName:   'banner.webp',
            temporaryPath:  '/tmp/phpABC',
            clientMimeType: 'image/webp',
            sizeBytes:      524288,
        );

        $data = $dto->jsonSerialize();

        self::assertArrayHasKey('originalName', $data);
        self::assertArrayHasKey('temporaryPath', $data);
        self::assertArrayHasKey('clientMimeType', $data);
        self::assertArrayHasKey('sizeBytes', $data);
        self::assertSame('banner.webp', $data['originalName']);
        self::assertSame(524288, $data['sizeBytes']);
    }
}
