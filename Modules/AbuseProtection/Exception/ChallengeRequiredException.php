<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Exception;

use Maatify\AbuseProtection\Domain\Enum\AbuseProtectionErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

/**
 * NOTE:
 * This exception is reserved for strict enforcement modes (API / JSON).
 * UI flows should rely on request attributes instead.
 */
final class ChallengeRequiredException extends AbuseProtectionSecurityException
{
    public function __construct()
    {
        parent::__construct('Security challenge required.');
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return AbuseProtectionErrorCodeEnum::CHALLENGE_REQUIRED;
    }
}
