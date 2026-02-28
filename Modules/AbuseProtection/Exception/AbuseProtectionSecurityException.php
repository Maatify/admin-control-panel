<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Exception;

use Maatify\Exceptions\Exception\Security\SecurityMaatifyException;
use Maatify\AbuseProtection\Domain\Policy\AbuseProtectionErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Throwable;

abstract class AbuseProtectionSecurityException extends SecurityMaatifyException
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
            policy: AbuseProtectionErrorPolicy::instance()
        );
    }
}
