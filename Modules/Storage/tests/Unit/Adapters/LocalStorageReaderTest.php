<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Adapters;

use DateTimeImmutable;
use Maatify\Storage\Adapters\LocalStorageReader;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for LocalStorageReader adapter.
 *
 * Verifies:
 * - Path validation (traversal, null bytes, empty paths)
 * - File existence checking
 * - Metadata retrieval (size, type, modification time)
 * - Access URL generation (returns relative path for local storage)
 */
final class LocalStorageReaderTest extends TestCase
{
    private string $storagePath;
    private LocalStorageReader $reader;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/storage-test-' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->reader = new LocalStorageReader($this->storagePath, new \Maatify\SharedCommon\Infrastructure\SystemClock(new \DateTimeZone('UTC')));
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->deleteDirectory($this->storagePath);
    }

    #[Test]
    public function getAccessUrlReturnsPathForExistingFile(): void
    {
        // Arrange
        $relativePath = 'images/test.jpg';
        $this->createTestFile($relativePath);

        // Act
        $result = $this->reader->getAccessUrl($relativePath);

        // Assert
        $this->assertInstanceOf(FileAccessResult::class, $result);
        $this->assertEquals($relativePath, $result->url);
        $this->assertEquals($relativePath, $result->path);
        $this->assertFalse($result->isSigned);
        $this->assertInstanceOf(DateTimeImmutable::class, $result->expiresAt);
    }

    #[Test]
    public function getAccessUrlDoesNotVerifyExistence(): void
    {
        // Arrange
        // File doesn't exist, but getAccessUrl() should NOT throw

        // Act
        $result = $this->reader->getAccessUrl('nonexistent/file.jpg');

        // Assert
        $this->assertInstanceOf(FileAccessResult::class, $result);
        $this->assertEquals('nonexistent/file.jpg', $result->path);
        $this->assertFalse($result->isSigned);
    }

    #[Test]
    public function existsReturnsTrueForExistingFile(): void
    {
        // Arrange
        $relativePath = 'images/test.jpg';
        $this->createTestFile($relativePath);

        // Act
        $exists = $this->reader->exists($relativePath);

        // Assert
        $this->assertTrue($exists);
    }

    #[Test]
    public function existsReturnsFalseForNonExistentFile(): void
    {
        // Act
        $exists = $this->reader->exists('nonexistent/file.jpg');

        // Assert
        $this->assertFalse($exists);
    }

    #[Test]
    public function getMetadataReturnsFileMetadata(): void
    {
        // Arrange
        $relativePath = 'documents/test.pdf';
        $this->createTestFile($relativePath, 'test content');

        // Act
        $metadata = $this->reader->getMetadata($relativePath);

        // Assert
        $this->assertInstanceOf(FileMetadata::class, $metadata);
        $this->assertGreaterThan(0, $metadata->sizeBytes);
        $this->assertInstanceOf(DateTimeImmutable::class, $metadata->lastModified);
        $this->assertNull($metadata->eTag);
    }

    #[Test]
    public function getMetadataThrowsFileNotFoundExceptionForNonExistentFile(): void
    {
        // Arrange
        $this->expectException(FileNotFoundException::class);

        // Act
        $this->reader->getMetadata('nonexistent/file.pdf');
    }

    #[Test]
    #[DataProvider('invalidPathProvider')]
    public function validatePathThrowsExceptionForInvalidPath(string $invalidPath, string $expectedExceptionType): void
    {
        // Arrange
        $this->expectException(InvalidPathException::class);

        // Act
        $this->reader->getAccessUrl($invalidPath);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function invalidPathProvider(): array
    {
        return [
            'empty path' => ['', InvalidPathException::class],
            'traversal attempt ..' => ['../../../etc/passwd', InvalidPathException::class],
            'traversal attempt /../' => ['files/../../../etc/passwd', InvalidPathException::class],
            'null byte' => ['file' . chr(0) . '.txt', InvalidPathException::class],
            'double slash' => ['files//double.txt', InvalidPathException::class],
            'backslash' => ['images\\test.jpg', InvalidPathException::class],
        ];
    }

    #[Test]
    public function doubleEncodedTraversalIsRejected(): void
    {
        // Arrange
        // %252e%252e -> %2e%2e -> ..
        $doubleEncodedTraversal = '%252e%252e/etc/passwd';
        $this->expectException(InvalidPathException::class);

        // Act
        $this->reader->getAccessUrl($doubleEncodedTraversal);
    }

    #[Test]
    public function getMetadataDetectsMimeType(): void
    {
        // Arrange
        $jpgPath = 'images/test.jpg';
        $this->createTestFile($jpgPath, 'fake image content');

        // Act
        $metadata = $this->reader->getMetadata($jpgPath);

        // Assert
        $this->assertNotNull($metadata->contentType);
        // finfo will detect based on magic bytes (text content = text/plain)
        // Extension-based fallback will return 'image/jpeg'
        // So we accept either
        $this->assertContains(
            $metadata->contentType,
            ['image/jpeg', 'text/plain'],
            "Expected image or text MIME type, got: {$metadata->contentType}"
        );
    }

    #[Test]
    public function getAccessUrlWithCustomExpiration(): void
    {
        // Arrange
        $relativePath = 'images/test.jpg';
        $this->createTestFile($relativePath);
        $expirationMinutes = 120;

        // Act
        $result = $this->reader->getAccessUrl($relativePath, $expirationMinutes);

        // Assert
        $this->assertEquals($expirationMinutes * 60, $result->expirationSeconds);
    }

    #[Test]
    public function pathWithLeadingSlashIsRejected(): void
    {
        // Arrange
        $pathWithSlash = '/images/test.jpg';
        $this->expectException(InvalidPathException::class);

        // Act
        $this->reader->getAccessUrl($pathWithSlash);
    }

    #[Test]
    public function getMetadataThrowsFileNotFoundExceptionForDirectory(): void
    {
        // Arrange
        mkdir($this->storagePath . '/images', 0755, true);

        $this->expectException(FileNotFoundException::class);

        // Act
        $this->reader->getMetadata('images');
    }

    #[Test]
    public function existsReturnsFalseForDirectory(): void
    {
        // Arrange
        mkdir($this->storagePath . '/images', 0755, true);

        // Act
        $exists = $this->reader->exists('images');

        // Assert
        $this->assertFalse($exists);
    }

    // Helper methods

    private function createTestFile(string $relativePath, string $content = 'test'): void
    {
        $absolutePath = $this->storagePath . '/' . ltrim($relativePath, '/');
        $directory = dirname($absolutePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, $content);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }
            if ($file->isDir()) {
                @rmdir($realPath);
            } else {
                @unlink($realPath);
            }
        }

        @rmdir($dir);
    }
}
