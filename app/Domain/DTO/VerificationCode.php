<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\IdentityType;
use App\Domain\Enum\VerificationCodeStatus;
use App\Domain\Enum\VerificationPurpose;
use DateTimeImmutable;

readonly class VerificationCode
{
    public function __construct(
        public int $id,
        public IdentityType $identityType,
        public string $identityId,
        public VerificationPurpose $purpose,
        public string $codeHash,
        public VerificationCodeStatus $status,
        public int $attempts,
        public int $maxAttempts,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt
    ) {
    }
}
