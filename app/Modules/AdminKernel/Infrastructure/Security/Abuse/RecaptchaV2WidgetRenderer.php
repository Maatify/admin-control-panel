<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;

/**
 * RecaptchaV2WidgetRenderer
 *
 * Renders Google reCAPTCHA v2 checkbox widget.
 *
 * Responsibilities:
 * - Render ready-to-embed HTML
 * - Expose provider metadata for UI/debugging
 *
 * IMPORTANT:
 * - No verification logic here
 * - No user input allowed
 */
final readonly class RecaptchaV2WidgetRenderer implements ChallengeWidgetRendererInterface
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
        return 'recaptcha_v2';
    }

    public function tokenFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    public function renderWidgetHtml(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $siteKey = htmlspecialchars((string) $this->siteKey, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="g-recaptcha" data-sitekey="{$siteKey}"></div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
    }
}
