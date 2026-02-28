<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class DocumentAlreadyAcceptedException extends ContentDocumentsConflictException
{
    public function __construct(
        string $message = 'Document version already accepted by this actor.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ContentDocumentsErrorCodeEnum::DOCUMENT_ALREADY_ACCEPTED;
    }
}
