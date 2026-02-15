<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

final class DocumentVersionImmutableException extends ContentDocumentsException
{
    public function __construct(string $message = 'Cannot modify translations of a published, active, or archived document version.')
    {
        parent::__construct($message);
    }
}
