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

    public function testMissingAndInactiveProfilesFailWithActionableErrors(): void
    {
        $missing = $this->service(null)->validateByCode('missing', $this->request());
        self::assertSame(['Profile not found.'], $missing->errors);

        $inactive = $this->service($this->profile(isActive: false))->validateByCode('avatar', $this->request());
        self::assertSame(['Profile is inactive.'], $inactive->errors);
    }

    public function testDimensionAndSizeLimitsAreEvaluatedIndependently(): void
    {
        $profile = $this->profile(
            minWidth: 100,
            minHeight: 100,
            maxWidth: 500,
            maxHeight: 500,
            maxSizeBytes: 1000,
        );
        $service = $this->service($profile);

        self::assertSame(
            ['Image width is below profile minimum.'],
            $service->validateByCode('avatar', $this->request(width: 99, height: 200, sizeBytes: 500))->errors,
        );
        self::assertSame(
            ['Image height is below profile minimum.'],
            $service->validateByCode('avatar', $this->request(width: 200, height: 99, sizeBytes: 500))->errors,
        );
        self::assertSame(
            ['Image width exceeds profile maximum.'],
            $service->validateByCode('avatar', $this->request(width: 501, height: 200, sizeBytes: 500))->errors,
        );
        self::assertSame(
            ['Image height exceeds profile maximum.'],
            $service->validateByCode('avatar', $this->request(width: 200, height: 501, sizeBytes: 500))->errors,
        );
        self::assertSame(
            ['Image file size exceeds profile maximum.'],
            $service->validateByCode('avatar', $this->request(width: 200, height: 200, sizeBytes: 1001))->errors,
        );
    }

    public function testExtensionAndMimeTypeListsAreCaseInsensitiveAndSupportDelimiters(): void
    {
        $service = $this->service($this->profile(
            allowedExtensions: ' JPG ; webp|png ',
            allowedMimeTypes: 'IMAGE/JPEG; image/webp|image/png',
        ));

        $result = $service->validateByCode('avatar', $this->request(
            extension: 'WebP',
            mimeType: 'IMAGE/WEBP',
        ));

        self::assertTrue($result->isValid);
        self::assertSame([], $result->errors);
    }

    public function testDisallowedMimeTypeAndTransparencyAreRejected(): void
    {
        $service = $this->service($this->profile(
            allowedMimeTypes: 'image/png',
            requiresTransparency: true,
        ));

        $mimeResult = $service->validateByCode('avatar', $this->request(mimeType: 'image/jpeg'));
        self::assertSame(['MIME type is not allowed by profile.'], $mimeResult->errors);

        $transparencyResult = $service->validateByCode('avatar', $this->request(
            mimeType: 'image/png',
            hasTransparency: false,
        ));
        self::assertSame(['Profile requires image transparency.'], $transparencyResult->errors);
    }

    public function testAspectRatioBoundariesAreInclusive(): void
    {
        $service = $this->service($this->profile(
            minAspectRatio: '1.0000',
            maxAspectRatio: '2.0000',
        ));

        self::assertTrue($service->validateByCode('avatar', $this->request(width: 1, height: 1))->isValid);
        self::assertTrue($service->validateByCode('avatar', $this->request(width: 2, height: 1))->isValid);
        self::assertSame(
            ['Image aspect ratio is below profile minimum.'],
            $service->validateByCode('avatar', $this->request(width: 99, height: 100))->errors,
        );
        self::assertSame(
            ['Image aspect ratio exceeds profile maximum.'],
            $service->validateByCode('avatar', $this->request(width: 201, height: 100))->errors,
        );
    }

    public function testNullFormatMetadataRemainsValidForMetadataOnlyRequests(): void
    {
        $service = $this->service($this->profile(
            allowedExtensions: 'png',
            allowedMimeTypes: 'image/png',
        ));

        self::assertTrue($service->validateByCode('avatar', $this->request(
            extension: null,
            mimeType: null,
        ))->isValid);
    }

    private function service(?ImageProfileDTO $profile): ImageProfileValidationService
    {
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->method('findByCode')->willReturn($profile);

        return new ImageProfileValidationService($query);
    }

    private function profile(
        ?int $minWidth = null,
        ?int $minHeight = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        ?int $maxSizeBytes = null,
        ?string $allowedExtensions = null,
        ?string $allowedMimeTypes = null,
        bool $isActive = true,
        ?string $minAspectRatio = null,
        ?string $maxAspectRatio = null,
        bool $requiresTransparency = false,
    ): ImageProfileDTO {
        return new ImageProfileDTO(
            id: 1,
            code: 'avatar',
            displayName: 'Avatar',
            minWidth: $minWidth,
            minHeight: $minHeight,
            maxWidth: $maxWidth,
            maxHeight: $maxHeight,
            maxSizeBytes: $maxSizeBytes,
            allowedExtensions: $allowedExtensions,
            allowedMimeTypes: $allowedMimeTypes,
            isActive: $isActive,
            notes: null,
            minAspectRatio: $minAspectRatio,
            maxAspectRatio: $maxAspectRatio,
            requiresTransparency: $requiresTransparency,
            preferredFormat: null,
            preferredQuality: null,
            variants: null,
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
        );
    }

    private function request(
        int $width = 300,
        int $height = 300,
        int $sizeBytes = 500,
        ?string $extension = 'png',
        ?string $mimeType = 'image/png',
        bool $hasTransparency = true,
    ): ImageValidationRequestDTO {
        return new ImageValidationRequestDTO(
            width: $width,
            height: $height,
            sizeBytes: $sizeBytes,
            extension: $extension,
            mimeType: $mimeType,
            hasTransparency: $hasTransparency,
        );
    }
}
