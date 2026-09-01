<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Services;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for ImageUploadService
 *
 * Tests include validation of:
 * - Default allowed extensions (jpg, jpeg, png, webp)
 * - Custom size limits
 * - Custom dimensions constraints
 * - Semantic basename generation (business context in filenames)
 */
final class ImageUploadServiceTest extends StorageModuleTestCase
{
    private ImageUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock storage adapter that stores files to temp directory
        $storageAdapter = $this->createMock(\Maatify\Storage\Contracts\StorageAdapterInterface::class);
        $storageAdapter
            ->method('store')
            ->willReturnCallback(function ($file, $path) {
                // Simulate storing file: return StoredFile DTO
                return new StoredFile(
                    path: $path,
                    url: $path,  // For testing, path and url are the same
                );
            });

        $fileUploadService = new FileUploadService($storageAdapter);
        $this->service = new ImageUploadService($fileUploadService);
    }

    public function testUploadsImageWithDefaults(): void
    {
        $file = $this->createMockUploadedFile('jpeg content', 'product.jpg');

        $path = $this->service->upload($file, 'products');

        $this->assertStringStartsWith('products/', $path->path);
        $this->assertStringEndsWith('.jpg', $path->path);
    }

    public function testUploadsImageWithCustomBaseName(): void
    {
        $file = $this->createMockJpegFile();

        $path = $this->service->upload(
            $file,
            'products',
            customBaseName: 'premium-tier-product'
        );

        $this->assertStringStartsWith('products/premium-tier-product-', $path->path);
        $this->assertStringEndsWith('.jpg', $path->path);
    }

    public function testSemanticNamingPreservesBusinessContext(): void
    {
        $file = $this->createMockPngFile();

        // Test product slug
        $productPath = $this->service->upload(
            $file,
            'products',
            customBaseName: 'awesome-product-v2'
        );
        $this->assertStringContainsString('awesome-product-v2-', $productPath->path);

        // Test payment method code
        $paymentPath = $this->service->upload(
            $file,
            'payment-methods',
            customBaseName: 'visa-mastercard-001'
        );
        $this->assertStringContainsString('visa-mastercard-001-', $paymentPath->path);

        // Test composite identifier
        $translationPath = $this->service->upload(
            $file,
            'translations',
            customBaseName: 'payment-method-123-lang-1'
        );
        $this->assertStringContainsString('payment-method-123-lang-1-', $translationPath->path);
    }

    public function testSemanticNamingSanitizesInput(): void
    {
        $file = $this->createMockJpegFile();

        // Special characters should be removed
        $path = $this->service->upload(
            $file,
            'products',
            customBaseName: 'Product@#$%!&*()'
        );

        $this->assertStringContainsString('product-', $path->path);
        // Path should not contain special characters
        $this->assertStringNotContainsString('@', $path->path);
        $this->assertStringNotContainsString('#', $path->path);
    }

    public function testUploadsImageWithCustomExtensions(): void
    {
        $file = $this->createMockWebpFile();

        $path = $this->service->upload(
            $file,
            'images',
            allowedExtensions: ['webp', 'avif']
        );

        $this->assertStringEndsWith('.webp', $path->path);
    }

    public function testUploadsImageWithSizeConstraint(): void
    {
        // Create large JPEG for testing size constraints
        $jpegMagic = "\xFF\xD8\xFF\xE0\x00\x10JFIF";
        $content = str_repeat('x', 500 * 1024); // 500KB
        $largeJpeg = $jpegMagic . $content;
        $file = $this->createMockUploadedFile($largeJpeg, 'image.jpg');

        $path = $this->service->upload(
            $file,
            'products',
            maxSizeBytes: 1024 * 1024 // 1MB
        );

        $this->assertNotEmpty($path);
    }

    public function testRejectsImageExceedingSizeLimit(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $content = str_repeat('x', 2 * 1024 * 1024); // 2MB
        $file = $this->createMockUploadedFile($content, 'image.jpg');

        $this->service->upload(
            $file,
            'products',
            maxSizeBytes: 1024 * 1024 // 1MB limit
        );
    }

    public function testRejectsDisallowedExtension(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $file = $this->createMockUploadedFile('content', 'script.exe');

        $this->service->upload(
            $file,
            'products',
            allowedExtensions: ['jpg', 'png']
        );
    }

    public function testSanitizesFilenamesToLowercase(): void
    {
        $file = $this->createMockUploadedFile('content', 'MyImage.JPG');

        $path = $this->service->upload(
            $file,
            'products',
            customBaseName: 'MyProduct'
        );

        // Should be lowercase
        $this->assertStringContainsString('myproduct-', $path->path);
        $this->assertStringEndsWith('.jpg', $path->path);
    }

    public function testGeneratesUniqueFilenames(): void
    {
        $file = $this->createMockUploadedFile('content', 'image.jpg');

        $path1 = $this->service->upload(
            $file,
            'products',
            customBaseName: 'same-basename'
        );

        $path2 = $this->service->upload(
            $file,
            'products',
            customBaseName: 'same-basename'
        );

        // Paths should be different even with same basename (due to random suffix)
        $this->assertNotEquals($path1->path, $path2->path);

        // But should have same basename
        $this->assertTrue(
            strpos($path1->path, 'same-basename-') === 9 && // after 'products/'
            strpos($path2->path, 'same-basename-') === 9
        );
    }

    public function testPreservesOriginalExtension(): void
    {
        // Only test default extensions (jpg, jpeg, png, webp)
        $extensions = ['jpg', 'png', 'webp'];

        foreach ($extensions as $ext) {
            $file = $this->createMockUploadedFile('content', "image.{$ext}");
            $path = $this->service->upload($file, 'images');

            $this->assertStringEndsWith(".{$ext}", $path->path);
        }
    }

    public function testNullCustomBaseNameUsesAutoGeneration(): void
    {
        $file = $this->createMockUploadedFile('content', 'my-awesome-product.jpg');

        $path = $this->service->upload(
            $file,
            'products',
            customBaseName: null // Explicitly null
        );

        // Should auto-generate from original filename
        $this->assertStringContainsString('my-awesome-product-', $path->path);
    }
}
