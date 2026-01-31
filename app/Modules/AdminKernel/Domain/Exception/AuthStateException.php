<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use DomainException;

class AuthStateException extends DomainException
{
    public const REASON_NOT_VERIFIED = 'not_verified';
    public const REASON_SUSPENDED = 'suspended';
    public const REASON_DISABLED = 'disabled';

    public function __construct(
        private readonly string $reason,
        string $message
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
