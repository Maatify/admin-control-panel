<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Exception;

use Maatify\Storage\Exception\AdapterException;
use Maatify\Storage\Exception\ConfigurationException;
use Maatify\Storage\Exception\FileUploadException;
use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Exception\StorageException;
use Maatify\Storage\Exception\StorageExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Storage module exceptions
 */
final class ExceptionTest extends TestCase
{
    public function testConfigurationExceptionMissingEnvVariable(): void
    {
        $exception = ConfigurationException::missingEnvVariable('API_KEY');

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertStringContainsString('API_KEY', $exception->getMessage());
        $this->assertStringContainsString('Missing required environment variable', $exception->getMessage());
    }

    public function testConfigurationExceptionUnsupportedDriver(): void
    {
        $exception = ConfigurationException::unsupportedDriver('s3');

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertStringContainsString('s3', $exception->getMessage());
        $this->assertStringContainsString('Unsupported storage driver', $exception->getMessage());
    }

    public function testConfigurationExceptionMissingAdapterConfig(): void
    {
        $exception = ConfigurationException::missingAdapterConfig('do_spaces');

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertStringContainsString('do_spaces', $exception->getMessage());
    }

    public function testFileUploadExceptionFromErrorCode(): void
    {
        $exception = FileUploadException::fromErrorCode(UPLOAD_ERR_NO_FILE);

        $this->assertInstanceOf(FileUploadException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertStringContainsString('uploaded', strtolower($exception->getMessage()));
    }

    public function testFileUploadExceptionUnreadableStream(): void
    {
        $exception = FileUploadException::unreadableStream();

        $this->assertInstanceOf(FileUploadException::class, $exception);
        $this->assertStringContainsString('stream', strtolower($exception->getMessage()));
    }

    public function testInvalidFileExceptionFileTooLarge(): void
    {
        $exception = InvalidFileException::fileTooLarge(10485760, 5242880); // 10MB vs 5MB

        $this->assertInstanceOf(InvalidFileException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertStringContainsString('exceeds', strtolower($exception->getMessage()));
    }

    public function testInvalidFileExceptionUnsupportedExtension(): void
    {
        $exception = InvalidFileException::unsupportedExtension('exe', ['jpg', 'png']);

        $this->assertInstanceOf(InvalidFileException::class, $exception);
        $this->assertStringContainsString('exe', strtolower($exception->getMessage()));
        $this->assertStringContainsString('jpg', strtolower($exception->getMessage()));
    }

    public function testInvalidFileExceptionImageTooSmall(): void
    {
        $dims = new \Maatify\Storage\Config\ImageDimensions(
            minWidth: 500,
            minHeight: 500,
            maxWidth: 1000,
            maxHeight: 1000,
        );
        $exception = InvalidFileException::imageTooSmall(100, 100, $dims);

        $this->assertInstanceOf(InvalidFileException::class, $exception);
        $this->assertStringContainsString('dimensions', strtolower($exception->getMessage()));
    }

    public function testInvalidFileExceptionImageTooLarge(): void
    {
        $dims = new \Maatify\Storage\Config\ImageDimensions(
            minWidth: 100,
            minHeight: 100,
            maxWidth: 500,
            maxHeight: 500,
        );
        $exception = InvalidFileException::imageTooLarge(1000, 1000, $dims);

        $this->assertInstanceOf(InvalidFileException::class, $exception);
        $this->assertStringContainsString('dimensions', strtolower($exception->getMessage()));
    }

    public function testAdapterExceptionFailedToReadLocalFile(): void
    {
        $exception = AdapterException::failedToReadLocalFile('/var/www/storage/drafts/7/3/photo.jpg');

        $this->assertInstanceOf(AdapterException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertStringContainsString('/var/www/storage/drafts/7/3/photo.jpg', $exception->getMessage());
    }

    public function testAdapterExceptionFailedToCopyFile(): void
    {
        $exception = AdapterException::failedToCopyFile('/source/photo.jpg', '/dest/photo.jpg');

        $this->assertInstanceOf(AdapterException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertStringContainsString('/source/photo.jpg', $exception->getMessage());
        $this->assertStringContainsString('/dest/photo.jpg', $exception->getMessage());
    }

    public function testStorageExceptionIsThrowable(): void
    {
        $exception = new StorageException('Test error');

        $this->assertInstanceOf(StorageException::class, $exception);
        $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertStringContainsString('Test error', $exception->getMessage());
    }

    public function testAllExceptionsImplementInterface(): void
    {
        $exceptions = [
            new ConfigurationException('test'),
            new FileUploadException('test'),
            new InvalidFileException('test'),
            new StorageException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(StorageExceptionInterface::class, $exception);
        }
    }
}
