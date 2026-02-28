<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Repository;

use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

interface DocumentTypeRepositoryInterface
{
    public function findById(int $id): ?DocumentType;

    public function findByKey(DocumentTypeKey $key): ?DocumentType;

    public function existsByKey(DocumentTypeKey $key): bool;

    /**
     * @return list<DocumentType>
     */
    public function findAll(): array;

    /**
     * @return list<DocumentTypeKey>
     */
    public function findAllKeys(): array;

    public function create(DocumentType $documentType): int;

    public function update(DocumentType $documentType): void;
}
