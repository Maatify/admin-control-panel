<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Adapter;

use Maatify\ImageProfile\Adapter\SlimUploadedFileAdapter;
use Maatify\ImageProfile\Exception\InvalidImageInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

#[CoversClass(\Maatify\ImageProfile\Adapter\SlimUploadedFileAdapter::class)]
final class SlimUploadedFileAdapterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUploadedFile(
        int     $error = UPLOAD_ERR_OK,
        string  $tmpPath = '/tmp/phpXYZ123',
        string  $clientName = 'photo.jpg',
        ?string $clientMime = 'image/jpeg',
        int     $size = 204800,
    ): UploadedFileInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')
               ->with('uri')
               ->willReturn($tmpPath);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn($error);
        $file->method('getStream')->willReturn($stream);
        $file->method('getClientFilename')->willReturn($clientName);
        $file->method('getClientMediaType')->willReturn($clientMime);
        $file->method('getSize')->willReturn($size);

        return $file;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_converts_valid_upload_to_dto(): void
    {
        $file = $this->makeUploadedFile();
        $dto  = SlimUploadedFileAdapter::toInputDTO($file);

        self::assertSame('photo.jpg', $dto->originalName);
        self::assertSame('/tmp/phpXYZ123', $dto->temporaryPath);
        self::assertSame('image/jpeg', $dto->clientMimeType);
        self::assertSame(204800, $dto->sizeBytes);
    }

    public function test_accepts_null_client_mime(): void
    {
        $file = $this->makeUploadedFile(clientMime: null);
        $dto  = SlimUploadedFileAdapter::toInputDTO($file);

        self::assertNull($dto->clientMimeType);
    }

    public function test_accepts_null_size_and_defaults_to_zero(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn('/tmp/phpXYZ');

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn(UPLOAD_ERR_OK);
        $file->method('getStream')->willReturn($stream);
        $file->method('getClientFilename')->willReturn('photo.jpg');
        $file->method('getClientMediaType')->willReturn(null);
        $file->method('getSize')->willReturn(null);

        $dto = SlimUploadedFileAdapter::toInputDTO($file);

        self::assertSame(0, $dto->sizeBytes);
    }

    // -------------------------------------------------------------------------
    // Upload error codes → InvalidImageInputException
    // -------------------------------------------------------------------------

    #[DataProvider('uploadErrorProvider')]
    public function test_throws_on_upload_error(int $errorCode): void
    {
        $this->expectException(InvalidImageInputException::class);

        SlimUploadedFileAdapter::toInputDTO($this->makeUploadedFile(error: $errorCode));
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

    // -------------------------------------------------------------------------
    // Invalid stream path
    // -------------------------------------------------------------------------

    public function test_throws_when_stream_uri_is_empty_string(): void
    {
        $this->expectException(InvalidImageInputException::class);

        $file = $this->makeUploadedFile(tmpPath: '');

        SlimUploadedFileAdapter::toInputDTO($file);
    }
}
