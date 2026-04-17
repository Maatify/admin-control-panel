<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Storage;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Maatify\ImageProfile\Exception\ImageProfileException;
use Maatify\ImageProfile\Storage\DoSpacesImageStorage;
use Maatify\ImageProfile\Tests\Fixtures\TestImageFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DoSpacesImageStorage using a mocked S3Client.
 *
 * No real network calls are made — the S3Client is replaced with a mock
 * that either succeeds silently or throws an AwsException.
 *
 */
#[CoversClass(\Maatify\ImageProfile\Storage\DoSpacesImageStorage::class)]
final class DoSpacesImageStorageTest extends TestCase
{
    private S3Client&MockObject $s3;

    protected function setUp(): void
    {
        // S3Client is final in AWS SDK v3 — mock with allowMockingFinalClasses
        // or use the AWS mock handler. We create a partial mock here.
        $this->s3 = $this->getMockBuilder(S3Client::class)
                         ->disableOriginalConstructor()
                         ->onlyMethods(['putObject', 'deleteObject', 'getEndpoint'])
                         ->getMock();

        $this->s3->method('getEndpoint')
                 ->willReturn('https://fra1.digitaloceanspaces.com');
    }

    protected function tearDown(): void
    {
        TestImageFactory::cleanup();
    }

    private function makeStorage(string $cdnBaseUrl = ''): DoSpacesImageStorage
    {
        return new DoSpacesImageStorage(
            client:     $this->s3,
            bucket:     'my-test-bucket',
            cdnBaseUrl: $cdnBaseUrl,
        );
    }

    // -------------------------------------------------------------------------
    // store() — happy path
    // -------------------------------------------------------------------------

    public function test_store_calls_put_object(): void
    {
        $this->s3->expects(self::once())
                 ->method('putObject');

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage();

        $storage->store($path, 'images/test/photo.jpg');
    }

    public function test_store_returns_stored_image_dto(): void
    {
        $this->s3->method('putObject')->willReturn([]);

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage();
        $result  = $storage->store($path, 'images/test/photo.jpg');

        self::assertSame('images/test/photo.jpg', $result->remotePath);
        self::assertSame('do-spaces', $result->disk);
        self::assertGreaterThan(0, $result->sizeBytes);
        self::assertNotEmpty($result->publicUrl);
        self::assertNotEmpty($result->mimeType);
    }

    public function test_store_builds_public_url_with_cdn_base(): void
    {
        $this->s3->method('putObject')->willReturn([]);

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage('https://cdn.example.com');
        $result  = $storage->store($path, 'images/banner.jpg');

        self::assertSame('https://cdn.example.com/images/banner.jpg', $result->publicUrl);
    }

    public function test_store_builds_public_url_without_cdn(): void
    {
        $this->s3->method('putObject')->willReturn([]);

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage('');
        $result  = $storage->store($path, 'images/banner.jpg');

        // URL should contain bucket + endpoint
        self::assertStringContainsString('my-test-bucket', $result->publicUrl);
        self::assertStringContainsString('images/banner.jpg', $result->publicUrl);
    }

    public function test_store_detects_jpeg_mime_type(): void
    {
        $this->s3->method('putObject')->willReturn([]);

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage();
        $result  = $storage->store($path, 'images/photo.jpg');

        self::assertSame('image/jpeg', $result->mimeType);
    }

    public function test_store_detects_png_mime_type(): void
    {
        $this->s3->method('putObject')->willReturn([]);

        $path    = TestImageFactory::png();
        $storage = $this->makeStorage();
        $result  = $storage->store($path, 'images/photo.png');

        self::assertSame('image/png', $result->mimeType);
    }

    // -------------------------------------------------------------------------
    // store() — AWS failure
    // -------------------------------------------------------------------------

    public function test_store_wraps_aws_exception_as_image_profile_exception(): void
    {
        $this->expectException(ImageProfileException::class);

        $awsException = $this->createMock(AwsException::class);
        $awsException->method('getMessage')->willReturn('Connection refused');
        $awsException->method('getAwsErrorMessage')->willReturn('Access Denied');

        $this->s3->method('putObject')->willThrowException($awsException);

        $path    = TestImageFactory::jpeg();
        $storage = $this->makeStorage();
        $storage->store($path, 'images/photo.jpg');
    }

    // -------------------------------------------------------------------------
    // delete() — happy path
    // -------------------------------------------------------------------------

    public function test_delete_calls_delete_object(): void
    {
        $this->s3->expects(self::once())
                 ->method('deleteObject')
                 ->with(self::arrayHasKey('Key'));

        $this->makeStorage()->delete('images/photo.jpg');
    }

    public function test_delete_passes_correct_bucket_and_key(): void
    {
        $this->s3->expects(self::once())
                 ->method('deleteObject')
                 ->with(self::callback(function (array $args): bool {
                     return $args['Bucket'] === 'my-test-bucket'
                         && $args['Key']    === 'images/banner.jpg';
                 }));

        $this->makeStorage()->delete('images/banner.jpg');
    }

    // -------------------------------------------------------------------------
    // delete() — AWS failure
    // -------------------------------------------------------------------------

    public function test_delete_wraps_aws_exception_as_image_profile_exception(): void
    {
        $this->expectException(ImageProfileException::class);

        $awsException = $this->createMock(AwsException::class);
        $awsException->method('getMessage')->willReturn('Not Found');
        $awsException->method('getAwsErrorMessage')->willReturn('NoSuchKey');

        $this->s3->method('deleteObject')->willThrowException($awsException);

        $this->makeStorage()->delete('images/ghost.jpg');
    }
}
