<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Infrastructure\Persistence\MySQL;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\Exception\DocumentActivationConflictException;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\DocumentVersionAlreadyExistsException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use PDO;
use PDOException;

final readonly class PdoDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findById(int $id): ?Document
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE id = :id LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByTypeAndVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version
    ): ?Document {
        $stmt = $this->pdo->prepare(
            'SELECT d.*
         FROM documents d
         JOIN document_types dt ON dt.id = d.document_type_id
         WHERE dt.`key` = :type_key
           AND d.version = :version
           AND d.archived_at IS NULL
         LIMIT 1'
        );

        $stmt->execute([
            'type_key' => (string) $typeKey,
            'version'  => (string) $version,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByIdNonArchived(int $id): ?Document
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM documents
             WHERE id = :id
               AND archived_at IS NULL
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findActiveByType(DocumentTypeKey $typeKey): ?Document
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*
             FROM documents d
             JOIN document_types dt ON dt.id = d.document_type_id
             WHERE dt.`key` = :type_key
                AND d.is_active = 1
                AND d.archived_at IS NULL
             LIMIT 1'
        );

        $stmt->execute(['type_key' => (string) $typeKey]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return list<Document>
     */
    public function findVersionsByType(DocumentTypeKey $typeKey): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*
         FROM documents d
         JOIN document_types dt ON dt.id = d.document_type_id
         WHERE dt.`key` = :type_key
           AND d.archived_at IS NULL
         ORDER BY d.created_at DESC'
        );

        $stmt->execute(['type_key' => (string) $typeKey]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $result[] = $this->hydrate($row);
        }

        return $result;
    }


    public function create(
        int $documentTypeId,
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO documents
                (document_type_id, type_key, version, requires_acceptance)
             VALUES
                (:document_type_id, :type_key, :version, :requires_acceptance)'
            );

            $stmt->execute([
                'document_type_id'    => $documentTypeId,
                'type_key'            => (string) $typeKey,
                'version'             => (string) $version,
                'requires_acceptance' => $requiresAcceptance ? 1 : 0,
            ]);

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new DocumentVersionAlreadyExistsException();
            }

            throw $e;
        }
    }

    public function archive(int $documentId, DateTimeImmutable $archivedAt): void
    {
        // Always force deactivation on archive (DB-level truth).
        $stmt = $this->pdo->prepare(
            'UPDATE documents
             SET is_active = 0,
                 archived_at = :archived_at
             WHERE id = :id
               AND archived_at IS NULL'
        );

        $stmt->execute([
            'archived_at' => $archivedAt->format('Y-m-d H:i:s'),
            'id'          => $documentId,
        ]);

        // rowCount === 0 => not found OR already archived
        // We leave the service to decide whether "already archived" is a no-op.
    }

    public function publish(int $documentId, DateTimeImmutable $publishedAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE documents
             SET published_at = :published_at
             WHERE id = :id
               AND archived_at IS NULL'
        );

        $stmt->execute([
            'published_at' => $publishedAt->format('Y-m-d H:i:s'),
            'id'           => $documentId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new DocumentNotFoundException();
        }
    }

    public function activate(int $documentId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE documents SET is_active = 1 WHERE id = :id'
        );

        try {
            $stmt->execute(['id' => $documentId]);
        } catch (\PDOException $e) {
            // MySQL integrity constraint violation (e.g. UNIQUE on active_guard)
            if ((string)$e->getCode() === '23000') {
                throw new DocumentActivationConflictException(previous: $e);
            }

            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw new DocumentNotFoundException();
        }
    }


    public function deactivate(int $documentId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE documents SET is_active = 0 WHERE id = :id'
        );

        $stmt->execute(['id' => $documentId]);

        if ($stmt->rowCount() === 0) {
            throw new DocumentNotFoundException();
        }
    }

    public function deactivateAllByTypeId(int $documentTypeId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE documents
             SET is_active = 0
             WHERE document_type_id = :document_type_id'
        );

        $stmt->execute(['document_type_id' => $documentTypeId]);
    }

    /**
     * @return list<Document>
     */
    public function findActivePublishedRequiringAcceptance(): array
    {
        $stmt = $this->pdo->query(
            'SELECT *
         FROM documents
         WHERE is_active = 1
           AND published_at IS NOT NULL
           AND requires_acceptance = 1
           AND archived_at IS NULL'
        );

        if (!$stmt instanceof \PDOStatement) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Document
    {
        $id                 = $row['id'] ?? null;
        $typeId             = $row['document_type_id'] ?? null;
        $typeKey            = $row['type_key'] ?? null;
        $version            = $row['version'] ?? null;
        $isActive           = $row['is_active'] ?? null;
        $requiresAcceptance = $row['requires_acceptance'] ?? null;
        $publishedAt        = $row['published_at'] ?? null;
        $archivedAt         = $row['archived_at'] ?? null;
        $createdAt          = $row['created_at'] ?? null;
        $updatedAt          = $row['updated_at'] ?? null;

        if (
            !is_numeric($id) ||
            !is_numeric($typeId) ||
            !is_string($typeKey) ||
            !is_string($version) ||
            !is_numeric($isActive) ||
            !is_numeric($requiresAcceptance) ||
            !is_string($createdAt)
        ) {
            throw new \RuntimeException('Invalid document row shape.');
        }

        return new Document(
            id: (int) $id,
            documentTypeId: (int) $typeId,
            typeKey: new DocumentTypeKey($typeKey),
            version: new DocumentVersion($version),
            isActive: (bool) $isActive,
            requiresAcceptance: (bool) $requiresAcceptance,
            publishedAt: is_string($publishedAt) ? new DateTimeImmutable($publishedAt) : null,
            archivedAt: is_string($archivedAt) ? new DateTimeImmutable($archivedAt) : null,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: is_string($updatedAt) ? new DateTimeImmutable($updatedAt) : null,
        );
    }
}
