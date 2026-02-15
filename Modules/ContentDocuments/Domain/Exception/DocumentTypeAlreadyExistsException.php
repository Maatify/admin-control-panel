<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentTypeAlreadyExistsException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Document type with this key already exists.'
    ) {
        parent::__construct($message);
    }
}
