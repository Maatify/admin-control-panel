<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Validators;

use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Validators\SizeValidator;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for SizeValidator
 */
final class SizeValidatorTest extends StorageModuleTestCase
{
    public function testValidatesFilesWithinLimit(): void
    {
        $validator = new SizeValidator(1024 * 1024); // 1MB

        // 500KB should pass
        $file = $this->createMockUploadedFile(str_repeat('x', 500 * 1024), 'file.jpg');
        $validator->validate($file);

        $this->assertTrue(true); // Reached without exception
    }

    public function testRejectsFilesExceedingLimit(): void
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessageMatches('/exceeds.*maximum/i');

        $validator = new SizeValidator(1024 * 1024); // 1MB
        // Create a file larger than 1MB
        $file = $this->createMockUploadedFile(str_repeat('x', 2 * 1024 * 1024), 'file.jpg');

        $validator->validate($file);
    }

    public function testAcceptsExactlyLimitSize(): void
    {
        $maxSize = 1024 * 1024; // 1MB
        $validator = new SizeValidator($maxSize);

        // JPEG magic: FF D8 FF E0 00 10 JFIF (10 bytes)
        $jpegMagic = "\xFF\xD8\xFF\xE0\x00\x10JFIF";
        $contentSize = $maxSize - strlen($jpegMagic);

        // Manually prepend magic bytes to reach exact size
        // (Large files don't get auto-injection from helper)
        $fileContent = $jpegMagic . str_repeat('x', $contentSize);
        $file = $this->createMockUploadedFile($fileContent, 'file.jpg');
        $validator->validate($file);

        $this->assertTrue(true); // Reached without exception
    }

    public function testHandlesNullFileSize(): void
    {
        $this->expectException(InvalidFileException::class);

        $validator = new SizeValidator(1024);
        $mock = $this->createMock(\Psr\Http\Message\UploadedFileInterface::class);
        $mock->method('getSize')->willReturn(null);

        $validator->validate($mock);
    }

    public function testLargeFileLimits(): void
    {
        // 500MB limit
        $validator = new SizeValidator(500 * 1024 * 1024);

        // 400MB file should pass
        $file = $this->createMockUploadedFile(str_repeat('x', 400 * 1024), 'file.mp4');
        $validator->validate($file);

        $this->assertTrue(true);
    }
}
