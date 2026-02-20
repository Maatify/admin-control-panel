<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Exception\Base\AdminKernelUnsupportedExceptionBase;

class UnsupportedNotificationChannelException extends AdminKernelUnsupportedExceptionBase
{
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('No sender supports the channel: %s', $channel));
    }
}
