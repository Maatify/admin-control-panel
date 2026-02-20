<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception\Base;

use Maatify\AdminKernel\Domain\Enum\AdminKernelErrorCodeEnum;
use Maatify\AdminKernel\Domain\Policy\AdminKernelErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\NotFound\NotFoundMaatifyException;
use Throwable;

abstract class AdminKernelNotFoundExceptionBase extends NotFoundMaatifyException
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
            $errorCodeOverride = AdminKernelErrorCodeEnum::NOT_FOUND;
        }

        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            meta: $meta,
            policy: AdminKernelErrorPolicy::instance(),
            httpStatusOverride: $httpStatusOverride
        );
    }

    public function defaultErrorCode(): ErrorCodeInterface
    {
        return AdminKernelErrorCodeEnum::NOT_FOUND;
    }
}
