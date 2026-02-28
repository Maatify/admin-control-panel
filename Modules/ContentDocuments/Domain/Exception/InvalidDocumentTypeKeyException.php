<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class InvalidDocumentTypeKeyException extends ContentDocumentsInvalidArgumentException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ContentDocumentsErrorCodeEnum::INVALID_DOCUMENT_TYPE_KEY;
    }
}
