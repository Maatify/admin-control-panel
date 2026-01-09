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
 * Presentation-only payload for OTP email delivery.
 *
 * SECURITY:
 * - Contains NO identifiers (user_id, admin_id, session_id).
 * - OTP is plaintext by design (short-lived).
 * - No timestamps or internal references.
 */
final readonly class OtpEmailPayloadDTO implements EmailPayloadInterface
{
    public function __construct(
        public ?string $display_name,
        public string $otp_code,
        public int $expires_in_minutes,
        public string $purpose,
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
            'otp_code'           => $this->otp_code,
            'expires_in_minutes' => $this->expires_in_minutes,
            'purpose'            => $this->purpose,
        ];
    }
}
