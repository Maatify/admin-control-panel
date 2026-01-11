<?php

declare(strict_types=1);

namespace App\Modules\Notification\Exception;

use RuntimeException;
use Throwable;

class NotificationQueueWriteException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
