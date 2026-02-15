<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Persistence;

use Maatify\ContentDocuments\Domain\Entity\DocumentTranslation;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTranslationRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContentDocumentsTestHelper;
use Tests\Support\MySQLTestHelper;

final class PdoDocumentTranslationRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();

        // Ensure language exists
        $this->pdo->exec("INSERT IGNORE INTO languages (id, code, name, is_active) VALUES (1, 'en', 'English', 1)");
    }

    public function testCreateThenUpdateAndFind(): void
    {
        $typeRepo = new PdoDocumentTypeRepository($this->pdo);
        $docRepo  = new PdoDocumentRepository($this->pdo);
        $trRepo   = new PdoDocumentTranslationRepository($this->pdo);

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

        // CREATE
        $trId = $trRepo->create(new DocumentTranslation(
            id: 0,
            documentId: $docId,
            languageId: 1,
            title: 'Terms',
            metaTitle: 'Terms Meta',
            metaDescription: 'Desc',
            content: '<p>Hello</p>',
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        self::assertGreaterThan(0, $trId);

        $found = $trRepo->findByDocumentAndLanguage($docId, 1);
        self::assertNotNull($found);
        self::assertSame('Terms', $found->title);
        self::assertSame($trId, $found->id);

        // UPDATE (explicit)
        $trRepo->update(new DocumentTranslation(
            id: $found->id,
            documentId: $docId,
            languageId: 1,
            title: 'Terms Updated',
            metaTitle: null,
            metaDescription: null,
            content: '<p>Updated</p>',
            createdAt: $found->createdAt,
            updatedAt: new \DateTimeImmutable('2024-01-02 00:00:00'),
        ));

        $found2 = $trRepo->findByDocumentAndLanguage($docId, 1);
        self::assertNotNull($found2);
        self::assertSame('Terms Updated', $found2->title);
        self::assertSame($found->id, $found2->id);

        $list = $trRepo->findByDocument($docId);
        self::assertNotEmpty($list);
    }

}
