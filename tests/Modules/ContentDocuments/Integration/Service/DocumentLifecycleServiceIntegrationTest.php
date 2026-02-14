<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Service;

use Maatify\ContentDocuments\Application\Service\DocumentLifecycleService;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoTransactionManager;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContentDocumentsTestHelper;
use Tests\Support\MySQLTestHelper;

final class DocumentLifecycleServiceIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();
    }

    public function testCreatePublishActivateThenActivateNewVersionDisablesOld(): void
    {
        $typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $docRepo  = new PdoDocumentRepository($this->pdo);
        $tx       = new PdoTransactionManager($this->pdo);

        $svc = new DocumentLifecycleService($docRepo, $typeRepo, $tx);

        $typeId = $typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $v1 = $svc->createVersion(new DocumentTypeKey('terms'), new DocumentVersion('v1'), true);
        $svc->publish($v1, new \DateTimeImmutable('2024-01-01 10:00:00'));
        $svc->activate($v1);

        $active1 = $docRepo->findActiveByType(new DocumentTypeKey('terms'));
        self::assertNotNull($active1);
        self::assertSame($v1, $active1->id);

        $v2 = $svc->createVersion(new DocumentTypeKey('terms'), new DocumentVersion('v2'), true);
        $svc->publish($v2, new \DateTimeImmutable('2024-01-02 10:00:00'));
        $svc->activate($v2);

        $active2 = $docRepo->findActiveByType(new DocumentTypeKey('terms'));
        self::assertNotNull($active2);
        self::assertSame($v2, $active2->id);

        $old = $docRepo->findById($v1);
        self::assertNotNull($old);
        self::assertFalse($old->isActive);

        self::assertSame($typeId, $old->documentTypeId);
    }
}
