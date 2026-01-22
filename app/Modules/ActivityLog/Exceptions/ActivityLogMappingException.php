<?php

declare(strict_types=1);

namespace App\Modules\ActivityLog\Exceptions;

use RuntimeException;
use Throwable;

final class ActivityLogMappingException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
