<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Services;

use DateTimeImmutable;
use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Services\FileReaderService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FileReaderService.
 *
 * Verifies:
 * - Delegation to adapter for getAccessUrl
 * - Delegation to adapter for exists
 * - Delegation to adapter for getMetadata
 * - Exception handling and propagation
 */
final class FileReaderServiceTest extends TestCase
{
    private ReaderAdapterInterface&MockObject $adapterMock;
    private FileReaderService $service;

    protected function setUp(): void
    {
        $this->adapterMock = $this->createMock(ReaderAdapterInterface::class);
        $this->service = new FileReaderService($this->adapterMock);
    }

    #[Test]
    public function getAccessUrlDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'images/test.jpg';
        $expirationMinutes = 60;
        $expectedResult = new FileAccessResult(
            url: 'https://cdn.example.com/images/test.jpg',
            path: 'images/test.jpg',
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
        $result = $this->service->getAccessUrl($path, $expirationMinutes);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function getAccessUrlWithDefaultExpiration(): void
    {
        // Arrange
        $path = 'videos/intro.mp4';
        $expectedResult = new FileAccessResult(
            url: 'https://cdn.example.com/videos/intro.mp4',
            path: 'videos/intro.mp4',
            expiresAt: new DateTimeImmutable(),
            expirationSeconds: 3600,
            isSigned: true,
        );

        $this->adapterMock
            ->expects($this->once())
            ->method('getAccessUrl')
            ->with($path, 60)
            ->willReturn($expectedResult);

        // Act
        $result = $this->service->getAccessUrl($path);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function existsDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'images/test.jpg';

        $this->adapterMock
            ->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        // Act
        $exists = $this->service->exists($path);

        // Assert
        $this->assertTrue($exists);
    }

    #[Test]
    public function getMetadataDelegatesToAdapter(): void
    {
        // Arrange
        $path = 'documents/test.pdf';
        $expectedMetadata = new FileMetadata(
            sizeBytes: 1024,
            lastModified: new DateTimeImmutable(),
            eTag: 'abc123',
            contentType: 'application/pdf',
        );

        $this->adapterMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with($path)
            ->willReturn($expectedMetadata);

        // Act
        $metadata = $this->service->getMetadata($path);

        // Assert
        $this->assertSame($expectedMetadata, $metadata);
    }

    #[Test]
    public function getAccessUrlPropagatesFileNotFoundException(): void
    {
        // Arrange
        $path = 'missing/file.jpg';
        $exception = FileNotFoundException::notFound($path);

        $this->adapterMock
            ->expects($this->once())
            ->method('getAccessUrl')
            ->with($path, 60)
            ->willThrowException($exception);

        $this->expectException(FileNotFoundException::class);

        // Act
        $this->service->getAccessUrl($path);
    }

    #[Test]
    public function getAccessUrlRejectsTraversalPath(): void
    {
        // Arrange
        $path = '../../../etc/passwd';

        // Adapter should NOT be called - validation happens in service layer
        $this->adapterMock->expects($this->never())->method('getAccessUrl');

        $this->expectException(InvalidPathException::class);

        // Act
        $this->service->getAccessUrl($path);
    }

    #[Test]
    public function getMetadataPropagatesFileNotFoundException(): void
    {
        // Arrange
        $path = 'missing/file.pdf';
        $exception = FileNotFoundException::notFound($path);

        $this->adapterMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with($path)
            ->willThrowException($exception);

        $this->expectException(FileNotFoundException::class);

        // Act
        $this->service->getMetadata($path);
    }
}
