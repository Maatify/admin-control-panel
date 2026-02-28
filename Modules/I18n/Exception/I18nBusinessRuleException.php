<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;
use Maatify\I18n\Domain\Policy\I18nErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Throwable;

abstract class I18nBusinessRuleException extends BusinessRuleMaatifyException
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
            policy: I18nErrorPolicy::instance()
        );
    }
}
