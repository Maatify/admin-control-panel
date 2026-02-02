<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Abuse;

final readonly class AbuseCookieIssueDTO
{
    public function __construct(
        public string $deviceId,
        public int $deviceTtlSeconds,
        public string $signature,
        public int $signatureTtlSeconds,
        public int $issuedAtUnix,
    ) {
    }
}
