<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim
 * @since       2026-02-02
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;

/**
 * TurnstileWidgetRenderer
 *
 * Renders Cloudflare Turnstile widget HTML.
 *
 * Responsibilities:
 * - Provide ready-to-embed HTML for UI rendering
 * - Hide all Turnstile-specific details from Twig
 * - Allow provider swapping without UI changes
 *
 * This class MUST NOT:
 * - Perform verification
 * - Read request data
 * - Contain business logic
 */
final readonly class TurnstileWidgetRenderer implements ChallengeWidgetRendererInterface
{
    public function __construct(
        private ?string $siteKey
    ) {}

    /**
     * Whether Turnstile is enabled.
     *
     * Enabled only if a valid site key is provided.
     */
    public function isEnabled(): bool
    {
        return is_string($this->siteKey) && $this->siteKey !== '';
    }

    /**
     * Provider identifier.
     */
    public function providerKey(): string
    {
        return 'turnstile';
    }

    /**
     * POST field name expected by Turnstile.
     */
    public function tokenFieldName(): string
    {
        return 'cf-turnstile-response';
    }

    /**
     * Render Turnstile widget HTML.
     *
     * Returns an empty string if the provider is disabled,
     * allowing controllers to stay simple.
     */
    public function renderWidgetHtml(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        // At this point, siteKey is guaranteed to be a non-empty string
        $siteKey = (string) $this->siteKey;

        $siteKeyEscaped = htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="cf-turnstile"
     data-sitekey="{$siteKeyEscaped}"
     data-theme="light"
     data-size="normal">
</div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
HTML;
    }

}
