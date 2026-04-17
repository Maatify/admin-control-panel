<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Adapter;

use Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::class)]
final class NativePhpUploadAdapterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}
     */
    private function validEntry(
        string $name = 'photo.jpg',
        string $type = 'image/jpeg',
        string $tmpName = '/tmp/phpXYZ123',
        int    $size = 204800,
    ): array {
        return [
            'name'     => $name,
            'type'     => $type,
            'tmp_name' => $tmpName,
            'error'    => UPLOAD_ERR_OK,
            'size'     => $size,
        ];
    }

    // -------------------------------------------------------------------------
    // fromFilesEntry() — happy path
    // -------------------------------------------------------------------------

    public function test_converts_valid_entry_to_dto(): void
    {
        $dto = \Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::fromFilesEntry($this->validEntry());

        self::assertSame('photo.jpg', $dto->originalName);
        self::assertSame('/tmp/phpXYZ123', $dto->temporaryPath);
        self::assertSame('image/jpeg', $dto->clientMimeType);
        self::assertSame(204800, $dto->sizeBytes);
    }

    public function test_empty_type_maps_to_null_client_mime(): void
    {
        $entry = $this->validEntry();
        $entry['type'] = '';

        $dto = \Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::fromFilesEntry($entry);

        self::assertNull($dto->clientMimeType);
    }

    public function test_size_zero_is_accepted(): void
    {
        $dto = NativePhpUploadAdapter::fromFilesEntry($this->validEntry(size: 0));

        self::assertSame(0, $dto->sizeBytes);
    }

    // -------------------------------------------------------------------------
    // fromFilesEntry() — upload error codes
    // -------------------------------------------------------------------------

    #[DataProvider('uploadErrorProvider')]
    public function test_throws_on_upload_error(int $errorCode): void
    {
        $this->expectException(InvalidImageInputException::class);

        $entry          = $this->validEntry();
        $entry['error'] = $errorCode;

        \Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::fromFilesEntry($entry);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function uploadErrorProvider(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE'   => [UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE'  => [UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL'    => [UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE'    => [UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION'  => [UPLOAD_ERR_EXTENSION],
        ];
    }

    public function test_exception_message_contains_filename(): void
    {
        $entry          = $this->validEntry(name: 'banner.webp');
        $entry['error'] = UPLOAD_ERR_INI_SIZE;

        try {
            \Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::fromFilesEntry($entry);
            self::fail('Expected InvalidImageInputException not thrown');
        } catch (InvalidImageInputException $e) {
            self::assertStringContainsString('banner.webp', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // fromFilesEntry() — field mapping edge cases
    // -------------------------------------------------------------------------

    public function test_preserves_original_file_name(): void
    {
        $dto = NativePhpUploadAdapter::fromFilesEntry($this->validEntry(name: 'my-banner.webp'));

        self::assertSame('my-banner.webp', $dto->originalName);
    }

    public function test_preserves_tmp_name_as_temporary_path(): void
    {
        $dto = \Maatify\ImageProfileLegacy\Adapter\NativePhpUploadAdapter::fromFilesEntry($this->validEntry(tmpName: '/tmp/phpABC987'));

        self::assertSame('/tmp/phpABC987', $dto->temporaryPath);
    }
}
