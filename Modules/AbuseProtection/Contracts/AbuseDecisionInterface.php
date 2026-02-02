<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Contracts;

use Maatify\AbuseProtection\DTO\AbuseContextDTO;

interface AbuseDecisionInterface
{
    public function requiresChallenge(AbuseContextDTO $context): bool;
}
