<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\DTO\ImageValidationRequestDTO;
use Maatify\ImageProfile\DTO\PaginationDTO;
use Maatify\ImageProfile\Service\ImageProfileValidationService;
use PHPUnit\Framework\TestCase;

final class ImageProfileValidationServiceTest extends TestCase
{
    public function testValidationSuccessAndFailureCases(): void
    {
        $profile = new ImageProfileDTO(
            id: 1,
            code: 'avatar',
            displayName: 'Avatar',
            minWidth: 100,
            minHeight: 100,
            maxWidth: 500,
            maxHeight: 500,
            maxSizeBytes: 100000,
            allowedExtensions: 'png,webp',
            allowedMimeTypes: 'image/png,image/webp',
            isActive: true,
            notes: null,
            minAspectRatio: '1.0000',
            maxAspectRatio: '1.0000',
            requiresTransparency: true,
            preferredFormat: null,
            preferredQuality: null,
            variants: null,
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
        );

        $query = new class($profile) implements ImageProfileQueryReaderInterface {
            public function __construct(private readonly ImageProfileDTO $profile) {}
            public function listProfiles(int $page, int $perPage, ?string $globalSearch, array $columnFilters): ImageProfilePaginatedResultDTO { return new ImageProfilePaginatedResultDTO([], new PaginationDTO(1, 20, 0, 0)); }
            public function listActiveProfiles(): array { return [$this->profile]; }
            public function findById(int $id): ?ImageProfileDTO { return $this->profile; }
            public function findByCode(string $code): ?ImageProfileDTO { return $code === 'avatar' ? $this->profile : null; }
        };

        $service = new ImageProfileValidationService($query);

        $ok = $service->validateByCode('avatar', new ImageValidationRequestDTO(
            width: 300,
            height: 300,
            sizeBytes: 50000,
            extension: 'png',
            mimeType: 'image/png',
            hasTransparency: true,
        ));
        self::assertTrue($ok->isValid);

        $bad = $service->validateByCode('avatar', new ImageValidationRequestDTO(
            width: 300,
            height: 300,
            sizeBytes: 50000,
            extension: 'jpg',
            mimeType: 'image/jpeg',
            hasTransparency: false,
        ));
        self::assertFalse($bad->isValid);
    }
}
