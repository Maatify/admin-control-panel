<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\DTO\PaginationDTO;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\Service\ImageProfileQueryService;
use PHPUnit\Framework\TestCase;

final class ImageProfileQueryServiceTest extends TestCase
{
    public function testPaginateDelegatesAllArguments(): void
    {
        $result = new ImageProfilePaginatedResultDTO([], new PaginationDTO(2, 10, 0, 0));
        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())
            ->method('listProfiles')
            ->with(2, 10, 'avatar', ['is_active' => 1])
            ->willReturn($result);

        self::assertSame(
            $result,
            (new ImageProfileQueryService($reader))->paginate(2, 10, 'avatar', ['is_active' => 1]),
        );
    }

    public function testActiveListWrapsReaderResultsAndSupportsIteration(): void
    {
        $profile = $this->profile(1, 'avatar');
        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())->method('listActiveProfiles')->willReturn([$profile]);

        $collection = (new ImageProfileQueryService($reader))->activeList();

        self::assertInstanceOf(ImageProfileCollectionDTO::class, $collection);
        self::assertFalse($collection->isEmpty());
        self::assertSame([$profile], iterator_to_array($collection));
        self::assertSame([$profile->jsonSerialize()], $collection->jsonSerialize());
    }

    public function testActiveListCanRepresentAnEmptyResult(): void
    {
        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())->method('listActiveProfiles')->willReturn([]);

        $collection = (new ImageProfileQueryService($reader))->activeList();

        self::assertTrue($collection->isEmpty());
        self::assertSame([], iterator_to_array($collection));
        self::assertSame([], $collection->jsonSerialize());
    }

    public function testGetByIdAndCodeReturnProfiles(): void
    {
        $profile = $this->profile(1, 'avatar');
        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())->method('findById')->with(1)->willReturn($profile);
        $reader->expects(self::once())->method('findByCode')->with('avatar')->willReturn($profile);

        $service = new ImageProfileQueryService($reader);
        self::assertSame($profile, $service->getById(1));
        self::assertSame($profile, $service->getByCode('avatar'));
    }

    public function testGetByIdAndCodeTranslateMissingProfiles(): void
    {
        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())->method('findById')->with(99)->willReturn(null);

        $service = new ImageProfileQueryService($reader);
        try {
            $service->getById(99);
            self::fail('Expected getById() to throw.');
        } catch (ImageProfileNotFoundException $exception) {
            self::assertStringContainsString('id 99', $exception->getMessage());
        }

        $reader = $this->createMock(ImageProfileQueryReaderInterface::class);
        $reader->expects(self::once())->method('findByCode')->with('missing')->willReturn(null);

        try {
            (new ImageProfileQueryService($reader))->getByCode('missing');
            self::fail('Expected getByCode() to throw.');
        } catch (ImageProfileNotFoundException $exception) {
            self::assertStringContainsString('code "missing"', $exception->getMessage());
        }
    }

    private function profile(int $id, string $code): ImageProfileDTO
    {
        return new ImageProfileDTO(
            id: $id,
            code: $code,
            displayName: 'Avatar',
            minWidth: null,
            minHeight: null,
            maxWidth: null,
            maxHeight: null,
            maxSizeBytes: null,
            allowedExtensions: null,
            allowedMimeTypes: null,
            isActive: true,
            notes: null,
            minAspectRatio: null,
            maxAspectRatio: null,
            requiresTransparency: false,
            preferredFormat: null,
            preferredQuality: null,
            variants: null,
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
        );
    }
}
