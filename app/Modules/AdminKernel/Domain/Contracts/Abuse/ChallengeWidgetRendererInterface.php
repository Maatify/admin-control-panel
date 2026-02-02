<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim
 * @since       2026-02-02
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Abuse;

/**
 * ChallengeWidgetRendererInterface
 *
 * UI adapter contract for rendering abuse-protection challenge widgets.
 *
 * Purpose:
 * - Twig must NOT know anything about Turnstile, hCaptcha, or reCAPTCHA.
 * - Controllers pass ready-to-render HTML to the view.
 * - Switching providers must NOT require Twig changes.
 *
 * This interface ONLY handles rendering concerns.
 * Verification is handled separately by ChallengeProviderInterface.
 */
interface ChallengeWidgetRendererInterface
{
    /**
     * Whether the challenge provider is effectively enabled
     * (e.g. site key is present and non-empty).
     */
    public function isEnabled(): bool;

    /**
     * Stable identifier for the challenge provider.
     *
     * Examples:
     * - "turnstile"
     * - "hcaptcha"
     * - "recaptcha_v2"
     *
     * Useful for debugging and logging.
     */
    public function providerKey(): string;

    /**
     * Name of the POST field expected by the provider
     * to carry the challenge token.
     *
     * Examples:
     * - Turnstile: "cf-turnstile-response"
     * - hCaptcha: "h-captcha-response"
     * - reCAPTCHA: "g-recaptcha-response"
     */
    public function tokenFieldName(): string;

    /**
     * Render the challenge widget as ready-to-embed HTML.
     *
     * IMPORTANT:
     * - Must return raw HTML only (div + script + attributes).
     * - Must NOT include any user-controlled input.
     * - No verification logic here.
     */
    public function renderWidgetHtml(): string;
}
