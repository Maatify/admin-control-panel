<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Email;

use Maatify\EmailDelivery\DTO\EmailPayloadInterface;

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
