<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\AppSettings\Domain\Policy\AppSettingsErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;
use Throwable;

abstract class AppSettingsBusinessRuleException extends BusinessRuleMaatifyException
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null,
        array $meta = []
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            meta: $meta,
            policy: AppSettingsErrorPolicy::instance()
        );
    }
}
