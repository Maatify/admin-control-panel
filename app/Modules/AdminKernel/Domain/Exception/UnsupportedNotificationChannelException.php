<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use DomainException;

class UnsupportedNotificationChannelException extends DomainException
{
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('No sender supports the channel: %s', $channel));
    }
}
