<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\Service;

use Maatify\ContentDocuments\Application\Service\DocumentLifecycleService;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentStateException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use PHPUnit\Framework\TestCase;

final class DocumentLifecycleServiceTest extends TestCase
{
    public function testCreateVersionThrowsWhenTypeNotFound(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $typeRepo->method('findByKey')->willReturn(null);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(DocumentTypeNotFoundException::class);
        $svc->createVersion(new DocumentTypeKey('terms'), new DocumentVersion('v1'), true);
    }

    public function testCreateVersionCallsRepositoryCreate(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $typeRepo->method('findByKey')->willReturn(
            new DocumentType(
                id: 7,
                key: new DocumentTypeKey('terms'),
                requiresAcceptanceDefault: true,
                isSystem: true,
                createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
                updatedAt: null,
            )
        );

        $docRepo->expects($this->once())
            ->method('create')
            ->with(
                7,
                $this->isInstanceOf(DocumentTypeKey::class),
                $this->isInstanceOf(DocumentVersion::class),
                true
            )
            ->willReturn(123);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $id = $svc->createVersion(new DocumentTypeKey('terms'), new DocumentVersion('v1'), true);
        self::assertSame(123, $id);
    }

    public function testPublishThrowsWhenDocumentNotFound(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $docRepo->method('findByIdNonArchived')->willReturn(null);
        $docRepo->method('findById')->willReturn(null);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(DocumentNotFoundException::class);
        $svc->publish(10, new \DateTimeImmutable('2024-01-01 10:00:00'));
    }

    public function testPublishNoOpWhenAlreadyPublished(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $docRepo->expects($this->never())->method('publish');

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $svc->publish(10, new \DateTimeImmutable('2024-01-02 10:00:00'));
        self::assertTrue(true);
    }

    public function testPublishCallsRepositoryWhenDraft(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: null,
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $docRepo->expects($this->once())->method('publish');

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $svc->publish(10, new \DateTimeImmutable('2024-01-02 10:00:00'));
    }

    public function testActivateThrowsWhenDocumentNotFound(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $docRepo->method('findByIdNonArchived')->willReturn(null);
        $docRepo->method('findById')->willReturn(null);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(DocumentNotFoundException::class);
        $svc->activate(10);
    }

    public function testActivateThrowsWhenUnpublished(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: null,
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(InvalidDocumentStateException::class);
        $svc->activate(10);
    }

    public function testActivateOwnedTransactionBeginCommitAndDeactivateActivateOrder(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 55,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $tx->method('inTransaction')->willReturn(false);
        $tx->expects($this->once())->method('begin');
        $tx->expects($this->once())->method('commit');
        $tx->expects($this->never())->method('rollback');

        $docRepo->expects($this->once())->method('deactivateAllByTypeId')->with(55);
        $docRepo->expects($this->once())->method('activate')->with(10);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);
        $svc->activate(10);
        self::assertTrue(true);
    }

    public function testActivateOwnedTransactionRollsBackOnFailure(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 55,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $tx->method('inTransaction')->willReturn(false);
        $tx->expects($this->once())->method('begin');
        $tx->expects($this->never())->method('commit');
        $tx->expects($this->once())->method('rollback');

        $docRepo->expects($this->once())->method('deactivateAllByTypeId')->with(55);
        $docRepo->expects($this->once())
            ->method('activate')
            ->with(10)
            ->willThrowException(new \RuntimeException('fail'));

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(\RuntimeException::class);
        $svc->activate(10);
    }

    public function testArchiveThrowsWhenDocumentNotFound(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $docRepo->method('findByIdNonArchived')->willReturn(null);
        $docRepo->method('findById')->willReturn(null);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(DocumentNotFoundException::class);
        $svc->archive(10, new \DateTimeImmutable('2024-01-03 10:00:00'));
    }

    public function testArchiveOwnedTransactionBeginCommitDeactivatesWhenActive(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 55,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: true,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $tx->method('inTransaction')->willReturn(false);
        $tx->expects($this->once())->method('begin');
        $tx->expects($this->once())->method('commit');
        $tx->expects($this->never())->method('rollback');

        $docRepo->expects($this->once())->method('deactivate')->with(10);
        $docRepo->expects($this->once())->method('archive')->with(
            10,
            $this->isInstanceOf(\DateTimeImmutable::class)
        );

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);
        $svc->archive(10, new \DateTimeImmutable('2024-01-03 10:00:00'));
        self::assertTrue(true);
    }

    public function testArchiveOwnedTransactionBeginCommitDoesNotDeactivateWhenNotActive(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 55,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: false,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $tx->method('inTransaction')->willReturn(false);
        $tx->expects($this->once())->method('begin');
        $tx->expects($this->once())->method('commit');
        $tx->expects($this->never())->method('rollback');

        $docRepo->expects($this->never())->method('deactivate');
        $docRepo->expects($this->once())->method('archive')->with(
            10,
            $this->isInstanceOf(\DateTimeImmutable::class)
        );

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);
        $svc->archive(10, new \DateTimeImmutable('2024-01-03 10:00:00'));
        self::assertTrue(true);
    }

    public function testArchiveOwnedTransactionRollsBackOnFailure(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $typeRepo = $this->createMock(DocumentTypeRepositoryInterface::class);
        $tx       = $this->createMock(TransactionManagerInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 55,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: true,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            archivedAt: null,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findByIdNonArchived')->willReturn($doc);
        $docRepo->method('findById')->willReturn($doc);

        $tx->method('inTransaction')->willReturn(false);
        $tx->expects($this->once())->method('begin');
        $tx->expects($this->never())->method('commit');
        $tx->expects($this->once())->method('rollback');

        $docRepo->expects($this->once())->method('deactivate')->with(10);
        $docRepo->expects($this->once())
            ->method('archive')
            ->with(10, $this->isInstanceOf(\DateTimeImmutable::class))
            ->willThrowException(new \RuntimeException('fail'));

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $this->expectException(\RuntimeException::class);
        $svc->archive(10, new \DateTimeImmutable('2024-01-03 10:00:00'));
    }
}
