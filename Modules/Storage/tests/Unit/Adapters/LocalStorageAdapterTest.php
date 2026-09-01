<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Adapters;

use Maatify\Storage\Adapters\LocalStorageAdapter;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\AdapterException;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for LocalStorageAdapter.
 *
 * Covers:
 * - storeFromPath(): copies a server-side file to storage, preserves source
 * - presign(): delegates to url() (no signing concept for local storage)
 * - url(): prepends baseUrl to relative path
 */
final class LocalStorageAdapterTest extends StorageModuleTestCase
{
    private LocalStorageAdapter $adapter;
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = $this->tempDir . '/storage';
        mkdir($this->basePath, 0777, true);

        $this->adapter = new LocalStorageAdapter(
            basePath: $this->basePath,
            baseUrl:  '/files',
        );
    }

    // ── storeFromPath ──────────────────────────────────────────────────────────

    #[Test]
    public function storeFromPath_copiesFileToDestination(): void
    {
        $source = $this->tempDir . '/source.jpg';
        file_put_contents($source, 'image-content');

        $this->adapter->storeFromPath($source, 'images/photo.jpg');

        $this->assertFileExists($this->basePath . '/images/photo.jpg');
        $this->assertEquals('image-content', file_get_contents($this->basePath . '/images/photo.jpg'));
    }

    #[Test]
    public function storeFromPath_preservesSourceFile(): void
    {
        $source = $this->tempDir . '/source.jpg';
        file_put_contents($source, 'image-content');

        $this->adapter->storeFromPath($source, 'images/photo.jpg');

        $this->assertFileExists($source);
    }

    #[Test]
    public function storeFromPath_returnsStoredFileWithCorrectPath(): void
    {
        $source = $this->tempDir . '/source.png';
        file_put_contents($source, 'png-content');

        $result = $this->adapter->storeFromPath($source, 'uploads/img.png');

        $this->assertInstanceOf(StoredFile::class, $result);
        $this->assertEquals('uploads/img.png', $result->path);
        $this->assertEquals('/files/uploads/img.png', $result->url);
    }

    #[Test]
    public function storeFromPath_stripsLeadingSlashFromDestinationPath(): void
    {
        $source = $this->tempDir . '/source.jpg';
        file_put_contents($source, 'content');

        $result = $this->adapter->storeFromPath($source, '/images/photo.jpg');

        $this->assertEquals('images/photo.jpg', $result->path);
        $this->assertFileExists($this->basePath . '/images/photo.jpg');
    }

    #[Test]
    public function storeFromPath_createsDestinationDirectoryIfMissing(): void
    {
        $source = $this->tempDir . '/source.jpg';
        file_put_contents($source, 'content');

        $this->assertDirectoryDoesNotExist($this->basePath . '/deep/nested/dir');

        $this->adapter->storeFromPath($source, 'deep/nested/dir/photo.jpg');

        $this->assertFileExists($this->basePath . '/deep/nested/dir/photo.jpg');
    }

    #[Test]
    public function storeFromPath_throwsAdapterExceptionWhenSourceMissing(): void
    {
        $this->expectException(AdapterException::class);
        $this->expectExceptionMessageMatches('/copy|Failed/i');

        $this->adapter->storeFromPath($this->tempDir . '/nonexistent.jpg', 'dest.jpg');
    }

    // ── presign ───────────────────────────────────────────────────────────────

    #[Test]
    public function presign_returnsLocalUrl(): void
    {
        $result = $this->adapter->presign('products/image.jpg');

        $this->assertEquals('/files/products/image.jpg', $result);
    }

    #[Test]
    public function presign_ignoresExpiryParameter(): void
    {
        $short  = $this->adapter->presign('products/image.jpg', 60);
        $long   = $this->adapter->presign('products/image.jpg', 86400);

        $this->assertEquals($short, $long);
    }

    // ── url ───────────────────────────────────────────────────────────────────

    #[Test]
    public function url_prependsBaseUrlToRelativePath(): void
    {
        $this->assertEquals('/files/products/image.jpg', $this->adapter->url('products/image.jpg'));
    }

    #[Test]
    public function url_stripsLeadingSlashFromRelativePath(): void
    {
        $this->assertEquals('/files/products/image.jpg', $this->adapter->url('/products/image.jpg'));
    }
}
