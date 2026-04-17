<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Storage;

use Maatify\ImageProfile\Storage\StoredImageDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Storage\StoredImageDTO::class)]
final class StoredImageDTOTest extends TestCase
{
    private function makeDTO(): StoredImageDTO
    {
        return new StoredImageDTO(
            publicUrl:  'https://cdn.example.com/images/categories/banner.webp',
            remotePath: 'images/categories/banner.webp',
            disk:       'do-spaces',
            sizeBytes:  524288,
            mimeType:   'image/webp',
        );
    }

    public function test_holds_all_fields(): void
    {
        $dto = $this->makeDTO();

        self::assertSame('https://cdn.example.com/images/categories/banner.webp', $dto->publicUrl);
        self::assertSame('images/categories/banner.webp', $dto->remotePath);
        self::assertSame('do-spaces', $dto->disk);
        self::assertSame(524288, $dto->sizeBytes);
        self::assertSame('image/webp', $dto->mimeType);
    }

    public function test_json_serialize_contains_all_keys(): void
    {
        $data = $this->makeDTO()->jsonSerialize();

        self::assertArrayHasKey('publicUrl', $data);
        self::assertArrayHasKey('remotePath', $data);
        self::assertArrayHasKey('disk', $data);
        self::assertArrayHasKey('sizeBytes', $data);
        self::assertArrayHasKey('mimeType', $data);
    }

    public function test_json_encode_round_trip(): void
    {
        $dto = $this->makeDTO();

        $decoded = json_decode((string) json_encode($dto, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var array{
         *     publicUrl: string,
         *     remotePath: string,
         *     disk: string,
         *     sizeBytes: int,
         *     mimeType: string
         * } $decoded
         */
        self::assertSame($dto->publicUrl, $decoded['publicUrl']);
        self::assertSame($dto->remotePath, $decoded['remotePath']);
        self::assertSame($dto->disk, $decoded['disk']);
        self::assertSame($dto->sizeBytes, $decoded['sizeBytes']);
        self::assertSame($dto->mimeType, $decoded['mimeType']);
    }
}
