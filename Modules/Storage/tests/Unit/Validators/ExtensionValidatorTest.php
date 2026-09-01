<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Validators;

use Maatify\Storage\Exception\InvalidFileException;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for ExtensionValidator
 */
final class ExtensionValidatorTest extends StorageModuleTestCase
{
    public function testValidatesAllowedExtensions(): void
    {
        $validator = new ExtensionValidator(['jpg', 'png', 'gif']);

        // Should not throw
        $file = $this->createMockUploadedFile('content', 'image.jpg');
        $validator->validate($file);

        $file = $this->createMockUploadedFile('content', 'image.png');
        $validator->validate($file);

        $this->assertTrue(true); // Reached without exception
    }

    public function testRejectsDisallowedExtensions(): void
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessageMatches('/exe.*Allowed/i');

        $validator = new ExtensionValidator(['jpg', 'png']);
        $file = $this->createMockUploadedFile('content', 'script.exe');

        $validator->validate($file);
    }

    public function testHandlesFilesWithoutExtension(): void
    {
        $this->expectException(InvalidFileException::class);

        $validator = new ExtensionValidator(['jpg', 'png']);
        $file = $this->createMockUploadedFile('content', 'filename_without_ext');

        $validator->validate($file);
    }

    public function testCaseInsensitiveValidation(): void
    {
        $validator = new ExtensionValidator(['jpg', 'png']);

        // JPG in uppercase should also work
        $file = $this->createMockUploadedFile('content', 'image.JPG');
        $validator->validate($file);

        $this->assertTrue(true); // Reached without exception
    }

    public function testMultipleExtensionHandling(): void
    {
        $validator = new ExtensionValidator(['jpeg', 'jpg', 'jpe']);

        $file = $this->createMockUploadedFile('content', 'image.jpeg');
        $validator->validate($file);

        $file = $this->createMockUploadedFile('content', 'image.jpe');
        $validator->validate($file);

        $this->assertTrue(true); // Reached without exception
    }
}
