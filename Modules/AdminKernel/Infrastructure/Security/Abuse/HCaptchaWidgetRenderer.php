<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;

final readonly class HCaptchaWidgetRenderer implements ChallengeWidgetRendererInterface
{
    public function __construct(
        private ?string $siteKey
    ) {}

    public function isEnabled(): bool
    {
        return is_string($this->siteKey) && $this->siteKey !== '';
    }

    public function providerKey(): string
    {
        return 'hcaptcha';
    }

    public function tokenFieldName(): string
    {
        return 'h-captcha-response';
    }

    public function renderWidgetHtml(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $siteKey = htmlspecialchars((string) $this->siteKey, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="h-captcha" data-sitekey="{$siteKey}"></div>
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
HTML;
    }
}
