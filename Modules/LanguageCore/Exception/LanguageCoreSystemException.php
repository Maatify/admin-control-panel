<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\Exceptions\Exception\System\SystemMaatifyException;
use Maatify\LanguageCore\Domain\Policy\LanguageCoreErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Throwable;

abstract class LanguageCoreSystemException extends SystemMaatifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            policy: LanguageCoreErrorPolicy::instance()
        );
    }
}
