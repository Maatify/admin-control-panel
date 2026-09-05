<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Persistence;

use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContentDocumentsTestHelper;
use Tests\Support\MySQLTestHelper;

final class PdoDocumentRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoDocumentTypeRepository $typeRepo;
    private PdoDocumentRepository $docRepo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();

        $this->typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $this->docRepo  = new PdoDocumentRepository($this->pdo);
    }

    public function testCreateFindPublishActivateAndFindActive(): void
    {
        $typeId = $this->typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $docId = $this->docRepo->create(
            $typeId,
            new DocumentTypeKey('terms'),
            new DocumentVersion('v1'),
            true
        );

        $doc = $this->docRepo->findById($docId);
        self::assertNotNull($doc);
        self::assertFalse($doc->isPublished());

        $this->docRepo->publish($docId, new \DateTimeImmutable('2024-01-01 10:00:00'));

        $doc2 = $this->docRepo->findById($docId);
        self::assertNotNull($doc2);
        self::assertTrue($doc2->isPublished());

        $this->docRepo->activate($docId);

        $active = $this->docRepo->findActiveByType(new DocumentTypeKey('terms'));
        self::assertNotNull($active);
        self::assertSame($docId, $active->id);
        self::assertTrue($active->isActive);
    }

    public function testDeactivateAllByTypeIdDisablesActive(): void
    {
        $typeId = $this->typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('privacy'),
            requiresAcceptanceDefault: false,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $docId = $this->docRepo->create(
            $typeId,
            new DocumentTypeKey('privacy'),
            new DocumentVersion('v1'),
            false
        );

        $this->docRepo->publish($docId, new \DateTimeImmutable('2024-01-01 10:00:00'));
        $this->docRepo->activate($docId);

        $this->docRepo->deactivateAllByTypeId($typeId);

        $active = $this->docRepo->findActiveByType(new DocumentTypeKey('privacy'));
        self::assertNull($active);
    }
}
