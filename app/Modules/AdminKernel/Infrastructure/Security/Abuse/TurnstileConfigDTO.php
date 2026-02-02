<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

readonly class TurnstileConfigDTO
{
    public function __construct(
        public ?string $siteKey,
        public ?string $secretKey,
    ) {}

}