<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\InvalidIdentifierStateException;
use DateTimeImmutable;

class AdminEmailVerificationService
{
    public function __construct(
        private readonly AdminEmailVerificationRepositoryInterface $repository
    ) {
    }

    public function verify(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Identifier is already verified.");
        }

        if ($currentStatus === VerificationStatus::FAILED) {
            throw new InvalidIdentifierStateException("Cannot verify a failed identifier.");
        }

        $this->repository->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));
    }

    public function startVerification(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Cannot reset a verified identifier.");
        }

        $this->repository->markPending($adminId);
    }

    public function failVerification(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Cannot fail a verified identifier.");
        }

        $this->repository->markFailed($adminId);
    }
}
