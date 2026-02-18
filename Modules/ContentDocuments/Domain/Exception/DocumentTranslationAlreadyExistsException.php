<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class DocumentTranslationAlreadyExistsException extends ContentDocumentsConflictException
{
    public function __construct(
        string $message = 'Document Translation already exists.'
    ) {
        parent::__construct($message);
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ContentDocumentsErrorCodeEnum::DOCUMENT_TRANSLATION_ALREADY_EXISTS;
    }
}
