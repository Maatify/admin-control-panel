<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Exception;

use Maatify\SharedCommon\Exception\MaatifyException;

final class DocumentAlreadyAcceptedException extends MaatifyException
{
    public function __construct(
        string $message = 'Document version already accepted by this actor.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
