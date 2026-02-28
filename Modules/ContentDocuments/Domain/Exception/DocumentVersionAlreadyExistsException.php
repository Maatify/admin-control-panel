<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class DocumentVersionAlreadyExistsException extends ContentDocumentsConflictException
{
    public function __construct(
        string $message = 'Document version already exists for this document type.'
    ) {
        parent::__construct($message);
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ContentDocumentsErrorCodeEnum::DOCUMENT_VERSION_ALREADY_EXISTS;
    }
}
