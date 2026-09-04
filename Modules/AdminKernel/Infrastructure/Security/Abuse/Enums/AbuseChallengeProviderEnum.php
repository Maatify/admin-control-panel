<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse\Enums;

enum AbuseChallengeProviderEnum: string
{
    case NONE = 'none';
    case TURNSTILE = 'turnstile';
    case HCAPTCHA = 'hcaptcha';
    case RECAPTCHA_V2 = 'recaptcha_v2';
}
