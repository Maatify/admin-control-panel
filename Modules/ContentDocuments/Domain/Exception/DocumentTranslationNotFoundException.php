<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentTranslationNotFoundException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Document Translation not found.'
    ) {
        parent::__construct($message);
    }
}
