<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentTypeKeyException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use PHPUnit\Framework\TestCase;

final class DocumentTypeKeyTest extends TestCase
{
    public function testValidKey(): void
    {
        $key = new DocumentTypeKey('terms');
        self::assertSame('terms', (string)$key);
    }

    public function testTrimsInput(): void
    {
        $key = new DocumentTypeKey('  privacy  ');
        self::assertSame('privacy', (string)$key);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidDocumentTypeKeyException::class);
        new DocumentTypeKey('   ');
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidDocumentTypeKeyException::class);
        new DocumentTypeKey('Terms');
    }

    public function testRejectsUnderscore(): void
    {
        $this->expectException(InvalidDocumentTypeKeyException::class);
        new DocumentTypeKey('terms_v1');
    }

    public function testRejectsInvalidChars(): void
    {
        $this->expectException(InvalidDocumentTypeKeyException::class);
        new DocumentTypeKey('terms!');
    }

    public function testEquals(): void
    {
        self::assertTrue((new DocumentTypeKey('refunds'))->equals(new DocumentTypeKey('refunds')));
        self::assertFalse((new DocumentTypeKey('refunds'))->equals(new DocumentTypeKey('privacy')));
    }
}

