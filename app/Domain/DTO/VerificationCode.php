<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\VerificationCodeStatus;
use DateTimeImmutable;

readonly class VerificationCode
{
    public function __construct(
        public int $id,
        public string $identityType,
        public string $identityId,
        public string $purpose,
        public string $codeHash,
        public VerificationCodeStatus $status,
        public int $attempts,
        public int $maxAttempts,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt
    ) {
    }
}
