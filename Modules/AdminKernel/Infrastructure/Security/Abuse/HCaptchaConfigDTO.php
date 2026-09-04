<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

final readonly class HCaptchaConfigDTO
{
    public function __construct(
        public ?string $siteKey,
        public ?string $secretKey,
    ) {}
}
