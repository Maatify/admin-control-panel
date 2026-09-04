<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Enum\AdminKernelErrorCodeEnum;
use Maatify\AdminKernel\Domain\Exception\Base\AdminKernelAuthorizationExceptionBase;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Throwable;

class StepUpRequiredException extends AdminKernelAuthorizationExceptionBase
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $message = 'Step-up authentication required',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null,
        array $meta = []
    ) {
        if ($errorCodeOverride === null) {
            $errorCodeOverride = AdminKernelErrorCodeEnum::STEP_UP_REQUIRED;
        }

        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            meta: $meta
        );
    }
}
