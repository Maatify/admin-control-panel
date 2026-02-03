<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Email;

use Maatify\EmailDelivery\DTO\EmailPayloadInterface;

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
