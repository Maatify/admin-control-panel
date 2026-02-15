<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentTranslationAlreadyExistsException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Document Translation already exists.'
    ) {
        parent::__construct($message);
    }
}
