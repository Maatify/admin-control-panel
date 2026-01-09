<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 20:57
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\DTO\Email;

/**
 * Payload for email ownership verification.
 *
 * SECURITY:
 * - OTP only (no long-lived tokens).
 * - No internal identifiers.
 * - No URLs with embedded secrets.
 */
final readonly class EmailVerificationPayloadDTO implements EmailPayloadInterface
{
    public function __construct(
        public ?string $display_name,
        public string $verification_code,
        public int $expires_in_minutes,
        public ?string $support_email = null,
    )
    {
    }

    /**
     * Export payload as array for rendering layer.
     */
    public function toArray(): array
    {
        return [
            'display_name'       => $this->display_name,
            'verification_code'  => $this->verification_code,
            'expires_in_minutes' => $this->expires_in_minutes,
            'support_email'      => $this->support_email,
        ];
    }
}
