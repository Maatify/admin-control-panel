<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentTypeNotFoundException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Document type not found.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
