<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Exception\Base\AdminKernelValidationExceptionBase;

class InvalidIdentifierFormatException extends AdminKernelValidationExceptionBase
{
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
