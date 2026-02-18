<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;
use Maatify\LanguageCore\Domain\Policy\LanguageCoreErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Throwable;

abstract class LanguageCoreBusinessRuleException extends BusinessRuleMaatifyException
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
