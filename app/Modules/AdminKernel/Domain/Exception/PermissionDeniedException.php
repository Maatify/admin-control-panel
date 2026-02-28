<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Exception\Base\AdminKernelAuthorizationExceptionBase;

class PermissionDeniedException extends AdminKernelAuthorizationExceptionBase
{
}
