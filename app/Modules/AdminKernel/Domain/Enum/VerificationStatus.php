<?php

namespace Maatify\AdminKernel\Domain\Enum;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
    case REPLACED = 'replaced';
}
