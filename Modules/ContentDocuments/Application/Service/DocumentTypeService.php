<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\Entity\DocumentType;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

final readonly class DocumentTypeService implements DocumentTypeServiceInterface
{
    public function __construct(
        private DocumentTypeRepositoryInterface $documentTypeRepository,
    ) {
    }

    /**
     * @return list<DocumentTypeDTO>
     */
    public function list(): array
    {
        $types = $this->documentTypeRepository->findAll();

        $out = [];
        foreach ($types as $type) {
            $out[] = $this->map($type);
        }

        return $out;
    }

    public function getById(int $id): ?DocumentTypeDTO
    {
        $type = $this->documentTypeRepository->findById($id);

        return $type === null ? null : $this->map($type);
    }

    public function getByKey(DocumentTypeKey $key): ?DocumentTypeDTO
    {
        $type = $this->documentTypeRepository->findByKey($key);

        return $type === null ? null : $this->map($type);
    }

    public function create(
        DocumentTypeKey $key,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): int {
        // createdAt/updatedAt are DB-truth; entity is immutable, so we pass a placeholder timestamp.
        $now = new DateTimeImmutable('now');

        return $this->documentTypeRepository->create(
            new DocumentType(
                id: 0,
                key: $key,
                requiresAcceptanceDefault: $requiresAcceptanceDefault,
                isSystem: $isSystem,
                createdAt: $now,
                updatedAt: null
            )
        );
    }

    public function update(
        int $id,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): void {
        $existing = $this->documentTypeRepository->findById($id);

        if ($existing === null) {
            throw new DocumentTypeNotFoundException('Document type not found.');
        }

        // key is immutable identity: never changed here
        $this->documentTypeRepository->update(
            new DocumentType(
                id: $existing->id,
                key: $existing->key,
                requiresAcceptanceDefault: $requiresAcceptanceDefault,
                isSystem: $isSystem,
                createdAt: $existing->createdAt,
                updatedAt: $existing->updatedAt
            )
        );
    }

    /**
     * @return list<DocumentTypeKey>
     */
    public function listRegisteredKeys(): array
    {
        return $this->documentTypeRepository->findAllKeys();
    }


    private function map(DocumentType $type): DocumentTypeDTO
    {
        return new DocumentTypeDTO(
            id: $type->id,
            key: (string) $type->key,
            requiresAcceptanceDefault: $type->requiresAcceptanceDefault,
            isSystem: $type->isSystem,
            createdAt: $type->createdAt,
            updatedAt: $type->updatedAt
        );
    }
}
