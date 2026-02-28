<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Service;

use Maatify\ContentDocuments\Application\Service\DocumentEnforcementService;
use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
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

final class DocumentEnforcementServiceIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();
    }

    public function testRequiresAcceptanceTrueThenFalseAfterAcceptance(): void
    {
        $typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $docRepo  = new PdoDocumentRepository($this->pdo);
        $accRepo  = new PdoDocumentAcceptanceRepository($this->pdo);


        $typeRepo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('privacy'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $typeKey = new DocumentTypeKey('privacy');
        $version = new DocumentVersion('v1');

        $docId = $docRepo->create(
            1, // document_types id is auto; safer to refetch but schema allows predictable if empty. keeping minimal.
            $typeKey,
            $version,
            true
        );

        $docRepo->publish($docId, new \DateTimeImmutable('2024-01-01 10:00:00'));
        $docRepo->activate($docId);

        $svc = new DocumentEnforcementService($docRepo, $accRepo);

        $actor = new ActorIdentity('user', 100);

        self::assertTrue($svc->requiresAcceptance($actor, $typeKey));

        $accRepo->save(new DocumentAcceptance(
            id: 0,
            actor: $actor,
            documentId: $docId,
            version: $version,
            acceptedAt: new \DateTimeImmutable('2024-01-01 11:00:00'),
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
        ));

        self::assertFalse($svc->requiresAcceptance($actor, $typeKey));
    }
}
