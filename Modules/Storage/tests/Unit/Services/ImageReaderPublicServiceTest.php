<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Services;

use DateTimeImmutable;
use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Services\FileReaderService;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\ImageReaderPublicService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ImageReaderPublicService.
 *
 * Verifies:
 * - Delegation to ImageReaderService
 * - Proper method forwarding for public image reads
 */
final class ImageReaderPublicServiceTest extends TestCase
{
    private ReaderAdapterInterface&MockObject $adapterMock;
    private FileReaderService $fileReaderService;
    private ImageReaderService $imageReaderService;
    private ImageReaderPublicService $imageReader;

    protected function setUp(): void
    {
        $this->adapterMock = $this->createMock(ReaderAdapterInterface::class);
        $this->fileReaderService = new FileReaderService($this->adapterMock);
        $this->imageReaderService = new ImageReaderService($this->fileReaderService);
        $this->imageReader = new ImageReaderPublicService($this->imageReaderService);
    }

    #[Test]
    public function getAccessUrlDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'products/gallery/item-123.jpg';
        $expirationMinutes = 60;
        $expectedResult = new FileAccessResult(
            url: 'https://cdn.example.com/products/gallery/item-123.jpg',
            path: 'products/gallery/item-123.jpg',
            expiresAt: new DateTimeImmutable(),
            expirationSeconds: 3600,
            isSigned: true,
        );

        $this->adapterMock
            ->expects($this->once())
            ->method('getAccessUrl')
            ->with($path, $expirationMinutes)
            ->willReturn($expectedResult);

        // Act
        $result = $this->imageReader->getAccessUrl($path, $expirationMinutes);

        // Assert
        $this->assertSame($expectedResult, $result);
        $this->assertEquals($path, $result->path);
        $this->assertTrue($result->isSigned);
    }

    #[Test]
    public function existsDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'products/gallery/item-123.jpg';

        $this->adapterMock
            ->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        // Act
        $exists = $this->imageReader->exists($path);

        // Assert
        $this->assertTrue($exists);
    }

    #[Test]
    public function getMetadataDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'products/gallery/item-123.jpg';
        $expectedMetadata = new FileMetadata(
            sizeBytes: 2048576,  // 2MB
            lastModified: new DateTimeImmutable(),
            eTag: 'image-etag-123',
            contentType: 'image/jpeg',
        );

        $this->adapterMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with($path)
            ->willReturn($expectedMetadata);

        // Act
        $metadata = $this->imageReader->getMetadata($path);

        // Assert
        $this->assertSame($expectedMetadata, $metadata);
        $this->assertEquals('image/jpeg', $metadata->contentType);
    }
}
