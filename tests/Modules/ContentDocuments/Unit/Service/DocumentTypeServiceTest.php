<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\Service;

use Maatify\ContentDocuments\Application\Service\DocumentTypeService;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeAlreadyExistsException;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use PHPUnit\Framework\TestCase;

final class DocumentTypeServiceTest extends TestCase
{
    public function testListReturnsMappedDTOs(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);

        $repo->method('findAll')->willReturn([
            new DocumentType(
                id: 1,
                key: new DocumentTypeKey('terms'),
                requiresAcceptanceDefault: true,
                isSystem: true,
                createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
                updatedAt: null
            ),
            new DocumentType(
                id: 2,
                key: new DocumentTypeKey('privacy'),
                requiresAcceptanceDefault: false,
                isSystem: true,
                createdAt: new \DateTimeImmutable('2024-01-02 00:00:00'),
                updatedAt: new \DateTimeImmutable('2024-01-03 00:00:00')
            ),
        ]);

        $svc = new DocumentTypeService($repo);
        $out = $svc->list();

        self::assertCount(2, $out);
        self::assertSame(1, $out[0]->id);
        self::assertSame('terms', $out[0]->key);
        self::assertTrue($out[0]->requiresAcceptanceDefault);
        self::assertTrue($out[0]->isSystem);

        self::assertSame(2, $out[1]->id);
        self::assertSame('privacy', $out[1]->key);
        self::assertFalse($out[1]->requiresAcceptanceDefault);
        self::assertTrue($out[1]->isSystem);
        self::assertInstanceOf(\DateTimeImmutable::class, $out[1]->updatedAt);
    }

    public function testGetByIdReturnsNullWhenNotFound(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $repo->method('findById')->with(10)->willReturn(null);

        $svc = new DocumentTypeService($repo);
        self::assertNull($svc->getById(10));
    }

    public function testGetByKeyReturnsNullWhenNotFound(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(null);

        $svc = new DocumentTypeService($repo);
        self::assertNull($svc->getByKey(new DocumentTypeKey('terms')));
    }

    public function testCreateCallsRepositoryCreateAndReturnsId(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);

        $repo->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(DocumentType::class))
            ->willReturn(123);

        $svc = new DocumentTypeService($repo);
        $id = $svc->create(new DocumentTypeKey('terms'), true, true);

        self::assertSame(123, $id);
    }

    public function testCreateBubblesAlreadyExistsExceptionFromRepo(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);

        $repo->expects($this->once())
            ->method('create')
            ->willThrowException(new DocumentTypeAlreadyExistsException());

        $svc = new DocumentTypeService($repo);

        $this->expectException(DocumentTypeAlreadyExistsException::class);
        $svc->create(new DocumentTypeKey('terms'), true, true);
    }

    public function testUpdateCallsRepositoryUpdateKeepingKeyImmutable(): void
    {
        $repo = $this->createMock(DocumentTypeRepositoryInterface::class);

        $existing = new DocumentType(
            id: 7,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: false,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null
        );

        $repo->method('findById')->with(7)->willReturn($existing);

        $repo->expects($this->once())
            ->method('update')
            ->with($this->callback(static function (DocumentType $dt): bool {
                return $dt->id === 7
                    && (string) $dt->key === 'terms'
                    && $dt->requiresAcceptanceDefault === true
                    && $dt->isSystem === false;
            }));

        $svc = new DocumentTypeService($repo);
        $svc->update(7, true, false);

        self::assertTrue(true);
    }

    public function testUpdateThrowsWhenTypeNotFound(): void
    {
        $repo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $repo->method('findById')->with(10)->willReturn(null);

        $repo->expects($this->never())->method('update');

        $svc = new \Maatify\ContentDocuments\Application\Service\DocumentTypeService($repo);

        $this->expectException(\Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException::class);

        $svc->update(10, true, false);
    }

}
