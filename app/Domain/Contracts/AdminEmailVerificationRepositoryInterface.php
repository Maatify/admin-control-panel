<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Enum\VerificationStatus;

interface AdminEmailVerificationRepositoryInterface
{
    public function getVerificationStatus(int $adminId): VerificationStatus;

    public function markVerified(int $adminId, string $timestamp): void;

    public function markFailed(int $adminId): void;

    public function markPending(int $adminId): void;
}
