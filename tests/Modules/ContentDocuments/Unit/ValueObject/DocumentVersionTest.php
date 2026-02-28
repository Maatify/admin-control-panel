<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentVersionException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use PHPUnit\Framework\TestCase;

final class DocumentVersionTest extends TestCase
{
    public function testValidVersion(): void
    {
        $v = new DocumentVersion('v1');
        self::assertSame('v1', (string)$v);
    }

    public function testTrimsInput(): void
    {
        $v = new DocumentVersion('  2026-01  ');
        self::assertSame('2026-01', (string)$v);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidDocumentVersionException::class);
        new DocumentVersion('   ');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidDocumentVersionException::class);
        new DocumentVersion(str_repeat('a', 33));
    }

    public function testEquals(): void
    {
        self::assertTrue((new DocumentVersion('v2'))->equals(new DocumentVersion('v2')));
        self::assertFalse((new DocumentVersion('v2'))->equals(new DocumentVersion('v3')));
    }
}
