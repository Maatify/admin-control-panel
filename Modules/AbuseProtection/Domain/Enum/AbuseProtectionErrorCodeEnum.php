<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum AbuseProtectionErrorCodeEnum: string implements ErrorCodeInterface
{
    case CHALLENGE_REQUIRED = 'CHALLENGE_REQUIRED';

    public function getValue(): string
    {
        return $this->value;
    }
}
