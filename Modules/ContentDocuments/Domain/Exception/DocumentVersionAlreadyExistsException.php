<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentVersionAlreadyExistsException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Document version already exists for this document type.'
    ) {
        parent::__construct($message);
    }
}
