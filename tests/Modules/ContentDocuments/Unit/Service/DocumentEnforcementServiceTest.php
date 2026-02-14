<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\Service;

use Maatify\ContentDocuments\Application\Service\DocumentEnforcementService;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use PHPUnit\Framework\TestCase;

final class DocumentEnforcementServiceTest extends TestCase
{
    public function testReturnsFalseWhenNoActiveDocument(): void
    {
        $docRepo = $this->createMock(DocumentRepositoryInterface::class);
        $accRepo = $this->createMock(DocumentAcceptanceRepositoryInterface::class);
        $typeRepo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $docRepo->method('findActiveByType')->willReturn(null);

        $svc = new DocumentEnforcementService($docRepo, $accRepo, $typeRepo);

        self::assertFalse(
            $svc->requiresAcceptance(new ActorIdentity('user', 1), new DocumentTypeKey('terms'))
        );
    }

    public function testReturnsFalseWhenActiveDocumentIsDraft(): void
    {
        $docRepo = $this->createMock(DocumentRepositoryInterface::class);
        $accRepo = $this->createMock(DocumentAcceptanceRepositoryInterface::class);
        $typeRepo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $docRepo->method('findActiveByType')->willReturn(
            new Document(
                id: 10,
                documentTypeId: 1,
                typeKey: new DocumentTypeKey('terms'),
                version: new DocumentVersion('v1'),
                isActive: true,
                requiresAcceptance: true,
                publishedAt: null, // draft
                createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
                updatedAt: null,
            )
        );

        $svc = new DocumentEnforcementService($docRepo, $accRepo, $typeRepo);

        self::assertFalse(
            $svc->requiresAcceptance(new ActorIdentity('user', 1), new DocumentTypeKey('terms'))
        );
    }

    public function testReturnsFalseWhenAcceptanceNotRequired(): void
    {
        $docRepo = $this->createMock(DocumentRepositoryInterface::class);
        $accRepo = $this->createMock(DocumentAcceptanceRepositoryInterface::class);
        $typeRepo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $docRepo->method('findActiveByType')->willReturn(
            new Document(
                id: 10,
                documentTypeId: 1,
                typeKey: new DocumentTypeKey('terms'),
                version: new DocumentVersion('v1'),
                isActive: true,
                requiresAcceptance: false,
                publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
                createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
                updatedAt: null,
            )
        );

        $svc = new DocumentEnforcementService($docRepo, $accRepo, $typeRepo);

        self::assertFalse(
            $svc->requiresAcceptance(new ActorIdentity('user', 1), new DocumentTypeKey('terms'))
        );
    }

    public function testReturnsFalseWhenAlreadyAccepted(): void
    {
        $docRepo = $this->createMock(DocumentRepositoryInterface::class);
        $accRepo = $this->createMock(DocumentAcceptanceRepositoryInterface::class);
        $typeRepo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: true,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findActiveByType')->willReturn($doc);

        $accRepo->expects($this->once())
            ->method('hasAccepted')
            ->with(
                $this->isInstanceOf(ActorIdentity::class),
                10,
                $this->isInstanceOf(DocumentVersion::class)
            )
            ->willReturn(true);

        $svc = new DocumentEnforcementService($docRepo, $accRepo, $typeRepo);

        self::assertFalse(
            $svc->requiresAcceptance(new ActorIdentity('user', 1), new DocumentTypeKey('terms'))
        );
    }

    public function testReturnsTrueWhenNotAcceptedYet(): void
    {
        $docRepo = $this->createMock(DocumentRepositoryInterface::class);
        $accRepo = $this->createMock(DocumentAcceptanceRepositoryInterface::class);
        $typeRepo = $this->createMock(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

        $doc = new Document(
            id: 10,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: true,
            requiresAcceptance: true,
            publishedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        );

        $docRepo->method('findActiveByType')->willReturn($doc);

        $accRepo->method('hasAccepted')->willReturn(false);

        $svc = new DocumentEnforcementService($docRepo, $accRepo, $typeRepo);

        self::assertTrue(
            $svc->requiresAcceptance(new ActorIdentity('user', 1), new DocumentTypeKey('terms'))
        );
    }
}
