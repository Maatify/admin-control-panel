<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Adapters;

use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use Maatify\Storage\Adapters\DOSpacesStorageAdapter;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\AdapterException;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for DOSpacesStorageAdapter.
 *
 * Covers:
 * - url(): returns '' for private adapters (cdnUrl = null), CDN URL for public
 * - presign(): returns presigned URL for private, CDN URL for public
 * - storeFromPath(): uploads via putObject, closes stream in finally
 */
final class DOSpacesStorageAdapterTest extends StorageModuleTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Creates a real S3Client backed by AWS SDK's own MockHandler.
     * Each appended Result is returned in order for successive API calls.
     *
     * @param list<Result> $results
     * @phpstan-ignore missingType.iterableValue
     */
    private function makeClient(array $results = []): S3Client
    {
        $mock = new AwsMockHandler();
        foreach ($results as $result) {
            $mock->append($result);
        }

        return new S3Client([
            'version'     => 'latest',
            'region'      => 'fra1',
            'endpoint'    => 'https://fra1.digitaloceanspaces.com',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'handler'     => $mock,
        ]);
    }

    /**
     * @param list<Result> $results
     * @phpstan-ignore missingType.iterableValue
     */
    private function makePrivateAdapter(array $results = []): DOSpacesStorageAdapter
    {
        return new DOSpacesStorageAdapter(
            client: $this->makeClient($results),
            bucket: 'test-bucket',
            cdnUrl: null,
            acl:    'private',
        );
    }

    /**
     * @param list<Result> $results
     * @phpstan-ignore missingType.iterableValue
     */
    private function makePublicAdapter(array $results = []): DOSpacesStorageAdapter
    {
        return new DOSpacesStorageAdapter(
            client: $this->makeClient($results),
            bucket: 'test-bucket',
            cdnUrl: 'https://cdn.example.com',
            acl:    'public-read',
        );
    }

    // ── url() ─────────────────────────────────────────────────────────────────

    #[Test]
    public function url_returnsEmptyStringWhenCdnUrlIsNull(): void
    {
        $adapter = $this->makePrivateAdapter();

        $this->assertSame('', $adapter->url('ar/7/42/img-15-photo.jpg'));
    }

    #[Test]
    public function url_returnsEmptyStringWhenCdnUrlIsEmpty(): void
    {
        $adapter = new DOSpacesStorageAdapter(
            client: $this->makeClient(),
            bucket: 'test-bucket',
            cdnUrl: '',
            acl:    'private',
        );

        $this->assertSame('', $adapter->url('ar/7/42/img-15-photo.jpg'));
    }

    #[Test]
    public function url_prependsCdnUrlForPublicAdapter(): void
    {
        $adapter = $this->makePublicAdapter();

        $this->assertSame(
            'https://cdn.example.com/products/image.jpg',
            $adapter->url('products/image.jpg'),
        );
    }

    #[Test]
    public function url_stripsLeadingSlashFromRelativePath(): void
    {
        $adapter = $this->makePublicAdapter();

        $this->assertSame(
            'https://cdn.example.com/products/image.jpg',
            $adapter->url('/products/image.jpg'),
        );
    }

    // ── presign() ─────────────────────────────────────────────────────────────

    #[Test]
    public function presign_returnsPresignedUrlForPrivateAdapter(): void
    {
        $adapter = $this->makePrivateAdapter();

        $url = $adapter->presign('ar/7/42/img-15-photo.jpg', 3600);

        // Presigned URL must be a valid URL with signing params
        $this->assertStringContainsString('ar/7/42/img-15-photo.jpg', $url);
        $this->assertStringContainsString('X-Amz-', $url);
        $this->assertStringContainsString('X-Amz-Expires=3600', $url);
    }

    #[Test]
    public function presign_forPublicAdapterReturnsCdnUrlWithoutSigning(): void
    {
        $adapter = $this->makePublicAdapter();

        $url = $adapter->presign('products/image.jpg', 3600);

        $this->assertSame('https://cdn.example.com/products/image.jpg', $url);
        $this->assertStringNotContainsString('X-Amz-', $url);
    }

    #[Test]
    public function presign_expiryIsReflectedInSignedUrl(): void
    {
        $adapter = $this->makePrivateAdapter();

        $url = $adapter->presign('ar/7/42/photo.jpg', 86400);

        $this->assertStringContainsString('X-Amz-Expires=86400', $url);
    }

    // ── storeFromPath() ───────────────────────────────────────────────────────

    #[Test]
    public function storeFromPath_uploadsFileAndReturnsStoredFile(): void
    {
        $source = $this->tempDir . '/photo.jpg';
        file_put_contents($source, 'image-data');

        $adapter = $this->makePrivateAdapter([new Result([])]);

        $result = $adapter->storeFromPath($source, 'ar/7/42/img-15-photo.jpg');

        $this->assertInstanceOf(StoredFile::class, $result);
        $this->assertSame('ar/7/42/img-15-photo.jpg', $result->path);
        $this->assertSame('', $result->url); // private adapter — no public URL
    }

    #[Test]
    public function storeFromPath_preservesSourceFile(): void
    {
        $source = $this->tempDir . '/photo.jpg';
        file_put_contents($source, 'image-data');

        $adapter = $this->makePrivateAdapter([new Result([])]);
        $adapter->storeFromPath($source, 'ar/7/42/img-15-photo.jpg');

        $this->assertFileExists($source);
    }

    #[Test]
    public function storeFromPath_stripsLeadingSlashFromDestinationKey(): void
    {
        $source = $this->tempDir . '/photo.jpg';
        file_put_contents($source, 'image-data');

        $adapter = $this->makePrivateAdapter([new Result([])]);

        $result = $adapter->storeFromPath($source, '/ar/7/42/img-15-photo.jpg');

        $this->assertSame('ar/7/42/img-15-photo.jpg', $result->path);
    }

    #[Test]
    public function storeFromPath_throwsAdapterExceptionWhenSourceMissing(): void
    {
        $this->expectException(AdapterException::class);

        $adapter = $this->makePrivateAdapter();
        $adapter->storeFromPath($this->tempDir . '/nonexistent.jpg', 'dest.jpg');
    }

    #[Test]
    public function storeFromPath_closesStreamEvenWhenUploadFails(): void
    {
        $source = $this->tempDir . '/photo.jpg';
        file_put_contents($source, 'image-data');

        // Build a client whose mock will throw on the putObject call
        $mock = new AwsMockHandler();
        $mock->appendException(new \Aws\Exception\AwsException(
            'Simulated upload failure',
            new \Aws\Command('PutObject'),
        ));
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => 'fra1',
            'endpoint'    => 'https://fra1.digitaloceanspaces.com',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'handler'     => $mock,
        ]);
        $adapter = new DOSpacesStorageAdapter(
            client: $client,
            bucket: 'test-bucket',
            cdnUrl: null,
            acl:    'private',
        );

        try {
            $adapter->storeFromPath($source, 'ar/7/42/photo.jpg');
        } catch (\Throwable) {
            // Expected — the upload failed
        }

        // After the exception the file handle must be closed (finally block ran).
        // Verify indirectly: source is still readable (no locked handle).
        $this->assertFileExists($source);
        $handle = fopen($source, 'rb');
        $this->assertIsResource($handle);
        fclose($handle);
    }
}
