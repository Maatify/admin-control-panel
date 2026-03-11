<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\DTO;

use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationCodeStatus;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;
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
        public DateTimeImmutable $createdAt,
        public ?string $createdIp = null,
        public ?string $usedIp = null
    ) {
    }
}
