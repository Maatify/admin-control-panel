<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception\Base;

use Maatify\AdminKernel\Domain\Enum\AdminKernelErrorCodeEnum;
use Maatify\AdminKernel\Domain\Policy\AdminKernelErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;
use Throwable;

abstract class AdminKernelBusinessRuleExceptionBase extends BusinessRuleMaatifyException
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null,
        array $meta = [],
        ?int $httpStatusOverride = null
    ) {
        if ($errorCodeOverride === null) {
            $errorCodeOverride = AdminKernelErrorCodeEnum::DOMAIN_NOT_ALLOWED;
        }

        parent::__construct(
            message           : $message,
            code              : $code,
            previous          : $previous,
            errorCodeOverride : $errorCodeOverride,
            httpStatusOverride: $httpStatusOverride,
            meta              : $meta,
            policy            : AdminKernelErrorPolicy::instance()
        );
    }

    public function defaultErrorCode(): ErrorCodeInterface
    {
        return AdminKernelErrorCodeEnum::DOMAIN_NOT_ALLOWED;
    }
}
