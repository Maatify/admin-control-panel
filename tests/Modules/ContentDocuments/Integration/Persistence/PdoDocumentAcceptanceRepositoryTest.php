<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Persistence;

use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentAlreadyAcceptedException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentAcceptanceRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContentDocumentsTestHelper;
use Tests\Support\MySQLTestHelper;

final class PdoDocumentAcceptanceRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();
    }

    public function testSaveThenHasAcceptedAndDuplicateThrows(): void
    {
        $typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $docRepo  = new PdoDocumentRepository($this->pdo);
        $accRepo  = new PdoDocumentAcceptanceRepository($this->pdo);

        $typeId = $typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $docId = $docRepo->create(
            $typeId,
            new DocumentTypeKey('terms'),
            new DocumentVersion('v1'),
            true
        );

        $actor = new ActorIdentity('user', 10);
        $version = new DocumentVersion('v1');

        self::assertFalse($accRepo->hasAccepted($actor, $docId, $version));

        $accRepo->save(new DocumentAcceptance(
            id: 0,
            actor: $actor,
            documentId: $docId,
            version: $version,
            acceptedAt: new \DateTimeImmutable('2024-01-01 12:00:00'),
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
        ));

        self::assertTrue($accRepo->hasAccepted($actor, $docId, $version));

        $this->expectException(DocumentAlreadyAcceptedException::class);

        $accRepo->save(new DocumentAcceptance(
            id: 0,
            actor: $actor,
            documentId: $docId,
            version: $version,
            acceptedAt: new \DateTimeImmutable('2024-01-01 12:01:00'),
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
        ));
    }

    public function testFindByActorReturnsList(): void
    {
        $typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $docRepo  = new PdoDocumentRepository($this->pdo);
        $accRepo  = new PdoDocumentAcceptanceRepository($this->pdo);

        $typeId = $typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('privacy'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $docId = $docRepo->create(
            $typeId,
            new DocumentTypeKey('privacy'),
            new DocumentVersion('v1'),
            true
        );

        $actor = new ActorIdentity('user', 20);

        $accRepo->save(new DocumentAcceptance(
            id: 0,
            actor: $actor,
            documentId: $docId,
            version: new DocumentVersion('v1'),
            acceptedAt: new \DateTimeImmutable('2024-01-01 12:00:00'),
            ipAddress: null,
            userAgent: null,
        ));

        $list = $accRepo->findByActor($actor);
        self::assertNotEmpty($list);
        self::assertSame('user', $list[0]->actor->actorType());
        self::assertSame(20, $list[0]->actor->actorId());
    }
}
