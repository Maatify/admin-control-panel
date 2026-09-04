<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Integration\Persistence;

use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContentDocumentsTestHelper;
use Tests\Support\MySQLTestHelper;

final class PdoDocumentTypeRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoDocumentTypeRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        ContentDocumentsTestHelper::reset();
        $this->repo = new PdoDocumentTypeRepository($this->pdo);
    }

    public function testCreateThenFindByKey(): void
    {
        $id = $this->repo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        self::assertGreaterThan(0, $id);

        $found = $this->repo->findByKey(new DocumentTypeKey('terms'));
        self::assertNotNull($found);
        self::assertSame('terms', (string) $found->key);
        self::assertTrue($found->requiresAcceptanceDefault);
        self::assertTrue($found->isSystem);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(DocumentTypeNotFoundException::class);

        $this->repo->update(new DocumentType(
            id: 999999,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: false,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));
    }

    public function testFindAllReturnsList(): void
    {
        $this->repo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('terms'),
            requiresAcceptanceDefault: true,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $this->repo->create(new DocumentType(
            id: 0,
            key: new DocumentTypeKey('privacy'),
            requiresAcceptanceDefault: false,
            isSystem: true,
            createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
            updatedAt: null,
        ));

        $all = $this->repo->findAll();
        self::assertNotEmpty($all);
    }
}
