<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

interface DocumentTypeServiceInterface
{
    /**
     * @return list<DocumentTypeDTO>
     */
    public function list(): array;

    public function getById(int $id): ?DocumentTypeDTO;

    public function getByKey(DocumentTypeKey $key): ?DocumentTypeDTO;

    public function create(
        DocumentTypeKey $key,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): int;

    public function update(
        int $id,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): void;

    /**
     * @return list<DocumentTypeKey>
     */
    public function listRegisteredKeys(): array;
}
