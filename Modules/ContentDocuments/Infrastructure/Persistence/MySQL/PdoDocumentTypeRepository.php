<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Infrastructure\Persistence\MySQL;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeAlreadyExistsException;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use PDO;
use PDOException;

final readonly class PdoDocumentTypeRepository implements DocumentTypeRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findById(int $id): ?DocumentType
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM document_types WHERE id = :id LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByKey(DocumentTypeKey $key): ?DocumentType
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM document_types WHERE `key` = :key LIMIT 1'
        );

        $stmt->execute(['key' => (string) $key]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return list<DocumentType>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM document_types');

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

    public function create(DocumentType $documentType): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO document_types
                (`key`, requires_acceptance_default, is_system)
             VALUES
                (:key, :requires_acceptance_default, :is_system)'
            );

            $stmt->execute([
                'key'                         => (string) $documentType->key,
                'requires_acceptance_default' => $documentType->requiresAcceptanceDefault ? 1 : 0,
                'is_system'                   => $documentType->isSystem ? 1 : 0,
            ]);

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new DocumentTypeAlreadyExistsException();
            }

            throw $e;
        }
    }


    public function update(DocumentType $documentType): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE document_types
             SET
                requires_acceptance_default = :requires_acceptance_default,
                is_system = :is_system
             WHERE id = :id'
        );

        $stmt->execute([
            'requires_acceptance_default' => $documentType->requiresAcceptanceDefault ? 1 : 0,
            'is_system'                   => $documentType->isSystem ? 1 : 0,
            'id'                          => $documentType->id,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new DocumentTypeNotFoundException();
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): DocumentType
    {
        $id        = $row['id'] ?? null;
        $key       = $row['key'] ?? null;
        $requires  = $row['requires_acceptance_default'] ?? null;
        $isSystem  = $row['is_system'] ?? null;
        $createdAt = $row['created_at'] ?? null;
        $updatedAt = $row['updated_at'] ?? null;

        if (
            !is_numeric($id) ||
            !is_string($key) ||
            !is_numeric($requires) ||
            !is_numeric($isSystem) ||
            !is_string($createdAt)
        ) {
            throw new \RuntimeException('Invalid document_type row shape.');
        }

        return new DocumentType(
            id: (int) $id,
            key: new DocumentTypeKey($key),
            requiresAcceptanceDefault: (bool) $requires,
            isSystem: (bool) $isSystem,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: is_string($updatedAt)
                ? new DateTimeImmutable($updatedAt)
                : null,
        );
    }
}
