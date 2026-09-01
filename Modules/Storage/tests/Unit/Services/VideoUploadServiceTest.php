<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Services;

use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Services\VideoUploadService;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for VideoUploadService
 *
 * Tests include validation of:
 * - Default allowed extensions (mp4, webm, mov, avi)
 * - Custom size limits
 * - Semantic basename generation (business context in filenames)
 */
final class VideoUploadServiceTest extends StorageModuleTestCase
{
    private VideoUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $storageAdapter = $this->createMock(\Maatify\Storage\Contracts\StorageAdapterInterface::class);
        $storageAdapter
            ->method('store')
            ->willReturnCallback(function ($file, $path) {
                return new StoredFile(
                    path: $path,
                    url: $path,  // For testing, path and url are the same
                );
            });

        $fileUploadService = new FileUploadService($storageAdapter);
        $this->service = new VideoUploadService($fileUploadService);
    }

    public function testUploadsVideoWithDefaults(): void
    {
        $file = $this->createMockMp4File('intro.mp4');

        $path = $this->service->upload($file, 'videos');

        $this->assertStringStartsWith('videos/', $path->path);
        $this->assertStringEndsWith('.mp4', $path->path);
    }

    public function testUploadsVideoWithCustomBaseName(): void
    {
        $file = $this->createMockUploadedFile('video content', 'video.mp4');

        $path = $this->service->upload(
            $file,
            'testimonials',
            customBaseName: 'customer-testimonial-001'
        );

        $this->assertStringStartsWith('testimonials/customer-testimonial-001-', $path->path);
        $this->assertStringEndsWith('.mp4', $path->path);
    }

    public function testSemanticNamingForWebinars(): void
    {
        $file = $this->createMockUploadedFile('content', 'webinar.mp4');

        $path = $this->service->upload(
            $file,
            'webinars',
            customBaseName: 'webinar-2024-march-keynote'
        );

        $this->assertStringContainsString('webinar-2024-march-keynote-', $path->path);
    }

    public function testSemanticNamingForCourses(): void
    {
        $file = $this->createMockUploadedFile('content', 'lesson.mp4');

        $path = $this->service->upload(
            $file,
            'courses',
            customBaseName: 'course-advanced-php-lesson-5'
        );

        $this->assertStringContainsString('course-advanced-php-lesson-5-', $path->path);
    }

    public function testUploadsMp4Video(): void
    {
        $file = $this->createMockUploadedFile('content', 'video.mp4');

        $path = $this->service->upload(
            $file,
            'videos',
            allowedExtensions: ['mp4']
        );

        $this->assertStringEndsWith('.mp4', $path->path);
    }

    public function testUploadsMultipleFormats(): void
    {
        $formats = ['mp4', 'webm', 'mov', 'avi'];

        foreach ($formats as $format) {
            $file = $this->createMockUploadedFile('content', "video.{$format}");

            $path = $this->service->upload(
                $file,
                'videos'
            );

            $this->assertStringEndsWith(".{$format}", $path->path);
        }
    }

    public function testUploadsVideoWithCustomSizeLimit(): void
    {
        // Create large MP4 (100MB in test is just 100KB for speed)
        $mp4Magic = "\x00\x00\x00\x20ftypisom";
        $content = str_repeat('x', 100 * 1024); // 100KB (simulating large file)
        $largeVideo = $mp4Magic . $content;
        $file = $this->createMockUploadedFile($largeVideo, 'video.mp4');

        $path = $this->service->upload(
            $file,
            'videos',
            maxSizeBytes: 500 * 1024 * 1024 // 500MB
        );

        $this->assertNotEmpty($path->path);
    }

    public function testRejectsVideoExceedingSizeLimit(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $content = str_repeat('x', 2 * 1024 * 1024); // 2MB
        $file = $this->createMockUploadedFile($content, 'video.mp4');

        $this->service->upload(
            $file,
            'videos',
            maxSizeBytes: 1024 * 1024 // 1MB limit
        );
    }

    public function testRejectsDisallowedFormat(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $file = $this->createMockUploadedFile('content', 'script.exe');

        $this->service->upload(
            $file,
            'videos',
            allowedExtensions: ['mp4', 'webm']
        );
    }

    public function testSanitizesBasenameCaseSensitivity(): void
    {
        $file = $this->createMockUploadedFile('content', 'MyVideo.MP4');

        $path = $this->service->upload(
            $file,
            'videos',
            customBaseName: 'WebinarEvent'
        );

        $this->assertStringContainsString('webinarevent-', $path->path);
        $this->assertStringEndsWith('.mp4', $path->path);
    }

    public function testGeneratesUniqueFilenamesEvenWithSameBaseName(): void
    {
        $file = $this->createMockUploadedFile('content', 'video.mp4');

        $path1 = $this->service->upload(
            $file,
            'videos',
            customBaseName: 'tutorial'
        );

        $path2 = $this->service->upload(
            $file,
            'videos',
            customBaseName: 'tutorial'
        );

        // Different paths due to random suffix
        $this->assertNotEquals($path1->path, $path2->path);

        // But same prefix
        $this->assertTrue(
            strpos($path1->path, 'tutorial-') === 7 &&
            strpos($path2->path, 'tutorial-') === 7
        );
    }

    public function testHandlesSpecialCharactersInBaseName(): void
    {
        $file = $this->createMockUploadedFile('content', 'video.mp4');

        $path = $this->service->upload(
            $file,
            'videos',
            customBaseName: 'Tutorial@2024#Advanced!Video&Pro'
        );

        // Should be sanitized
        $this->assertStringContainsString('tutorial2024advancedvideopro-', $path->path);
    }

    public function testNullCustomBaseNameAutoGenerates(): void
    {
        $file = $this->createMockUploadedFile('content', 'my-tutorial-video.mp4');

        $path = $this->service->upload(
            $file,
            'videos',
            customBaseName: null
        );

        // Should auto-generate from filename
        $this->assertStringContainsString('my-tutorial-video-', $path->path);
    }

    public function testLargeFilesWithSemanticNaming(): void
    {
        // Create large MP4 for testing (300MB in test is just 300KB for speed)
        $mp4Magic = "\x00\x00\x00\x20ftypisom";
        $content = str_repeat('x', 300 * 1024);
        $largeVideo = $mp4Magic . $content;
        $file = $this->createMockUploadedFile($largeVideo, 'large.mp4');

        $path = $this->service->upload(
            $file,
            'courses',
            maxSizeBytes: 500 * 1024 * 1024, // 500MB
            customBaseName: 'advanced-course-part-1'
        );

        $this->assertStringContainsString('advanced-course-part-1-', $path->path);
        $this->assertStringEndsWith('.mp4', $path->path);
    }
}
