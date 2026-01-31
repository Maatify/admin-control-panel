<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use LogicException;

class InvalidIdentifierFormatException extends LogicException
{
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
