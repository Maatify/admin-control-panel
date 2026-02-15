<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Persistence;

use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTranslationRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class PdoDocumentRepositoryVersionsWithTranslationsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->pdo->exec('TRUNCATE TABLE document_translations');
        $this->pdo->exec('TRUNCATE TABLE documents');
        $this->pdo->exec('TRUNCATE TABLE document_types');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Ensure language exists if FK exists in translations table (depends on your schema)
        $this->pdo->exec("INSERT IGNORE INTO languages (id, code, name, is_active) VALUES (1, 'en', 'English', 1)");
    }

    public function testFindVersionsWithTranslationsDoesNotCorruptDocumentId(): void
    {
        // Arrange: create a document type
        $this->pdo->exec("INSERT INTO document_types (id, `key`) VALUES (1, 'terms')");

        $docRepo = new PdoDocumentRepository($this->pdo);
        $trRepo  = new PdoDocumentTranslationRepository($this->pdo);

        $docId = $docRepo->create(
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            requiresAcceptance: true
        );

        // Insert translation (will have its own id distinct from document id)
        $trRepo->save(new \Maatify\ContentDocuments\Domain\Entity\DocumentTranslation(
            id: 0,
            documentId: $docId,
            languageId: 1,
            title: 'Terms',
            metaTitle: null,
            metaDescription: null,
            content: '<p>Hi</p>',
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        // Act
        $versions = $docRepo->findVersionsWithTranslationsByTypeAndLanguage(new DocumentTypeKey('terms'), 1);

        // Assert
        self::assertCount(1, $versions);
        self::assertSame($docId, $versions[0]->id, 'Document id must come from documents table, not translations.');
    }
}
