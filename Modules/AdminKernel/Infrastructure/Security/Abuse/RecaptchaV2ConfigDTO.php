<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

/**
 * RecaptchaV2ConfigDTO
 *
 * Holds Google reCAPTCHA v2 configuration values.
 *
 * This DTO is runtime-only and injected via the container.
 * No validation or fallback logic belongs here.
 */
final readonly class RecaptchaV2ConfigDTO
{
    public function __construct(
        public ?string $siteKey,
        public ?string $secretKey,
    ) {}
}
