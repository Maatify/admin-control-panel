<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationCodeStatus;
use App\Domain\Enum\VerificationPurposeEnum;
use DateTimeImmutable;

readonly class VerificationCode
{
    public function __construct(
        public int $id,
        public IdentityTypeEnum $identityType,
        public string $identityId,
        public VerificationPurposeEnum $purpose,
        public string $codeHash,
        public VerificationCodeStatus $status,
        public int $attempts,
        public int $maxAttempts,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt
    ) {
    }
}
