<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class DocumentVersionImmutableException extends ContentDocumentsException
{
    public function __construct(
        string $message = 'Cannot modify translations of a published, active, or archived document version.'
    ) {
        parent::__construct($message);
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ContentDocumentsErrorCodeEnum::DOCUMENT_VERSION_IMMUTABLE;
    }
}
