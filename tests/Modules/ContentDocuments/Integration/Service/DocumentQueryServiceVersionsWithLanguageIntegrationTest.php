<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Service;

use Maatify\ContentDocuments\Application\Service\DocumentQueryService;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionWithTranslationDTO;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTranslationRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class DocumentQueryServiceVersionsWithLanguageIntegrationTest extends TestCase
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

        // Ensure language exists (if translations table references languages)
        $this->pdo->exec("INSERT IGNORE INTO languages (id, code, name, is_active) VALUES (1, 'en', 'English', 1)");
    }

    public function testGetVersionsWithLanguageReturnsTranslationOrNullPerVersion(): void
    {
        // Arrange
        $this->pdo->exec("INSERT INTO document_types (id, `key`) VALUES (1, 'terms')");

        $docRepo = new PdoDocumentRepository($this->pdo);
        $trRepo  = new PdoDocumentTranslationRepository($this->pdo);

        $typeKey = new DocumentTypeKey('terms');

        $docIdV1 = $docRepo->create(
            documentTypeId: 1,
            typeKey: $typeKey,
            version: new DocumentVersion('v1'),
            requiresAcceptance: true
        );

        $docIdV2 = $docRepo->create(
            documentTypeId: 1,
            typeKey: $typeKey,
            version: new DocumentVersion('v2'),
            requiresAcceptance: true
        );

        // Only v2 has translation
        $trRepo->save(new \Maatify\ContentDocuments\Domain\Entity\DocumentTranslation(
            id: 0,
            documentId: $docIdV2,
            languageId: 1,
            title: 'Terms v2',
            metaTitle: null,
            metaDescription: null,
            content: '<p>V2</p>',
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $service = new DocumentQueryService(
            documentRepository: $docRepo,
            translationRepository: $trRepo
        );

        // Act
        $rows = $service->getVersionsWithLanguage($typeKey, 1);

        // Assert
        self::assertNotEmpty($rows);
        foreach ($rows as $row) {
            self::assertInstanceOf(DocumentVersionWithTranslationDTO::class, $row);
        }

        // Map results by version for stable assertions
        $byVersion = [];
        foreach ($rows as $row) {
            $byVersion[$row->document->version] = $row;
        }

        self::assertArrayHasKey('v1', $byVersion);
        self::assertArrayHasKey('v2', $byVersion);

        // v1 => translation null
        self::assertNull($byVersion['v1']->translation);
        self::assertSame($docIdV1, $byVersion['v1']->document->id);

        // v2 => translation exists
        self::assertNotNull($byVersion['v2']->translation);
        self::assertSame($docIdV2, $byVersion['v2']->document->id);
        self::assertSame('Terms v2', $byVersion['v2']->translation->title);
        self::assertSame(1, $byVersion['v2']->translation->languageId);
        self::assertSame($docIdV2, $byVersion['v2']->translation->documentId);
    }
}
