<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Exception;

use RuntimeException;

// NOTE:
// This exception is reserved for strict enforcement modes (API / JSON).
// UI flows should rely on request attributes instead.
final class ChallengeRequiredException extends RuntimeException
{
}
