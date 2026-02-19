<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Policy\AdminKernelErrorPolicy;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class AdminKernelException extends MaatifyException
{
    protected static function defaultPolicy(): ErrorPolicyInterface
    {
        return AdminKernelErrorPolicy::instance();
    }
}
