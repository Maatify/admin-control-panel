<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-31 13:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Crypto;

/**
 * Canonical Crypto Context Registry
 *
 * - All contexts MUST be versioned (:vX)
 * - Contexts MUST NOT be user-defined
 * - Contexts MUST be documented before use
 *
 * This file is the single source of truth.
 */
final class CryptoContext
{
    /* ===============================
     * Email / Notification System
     * =============================== */

    public const NOTIFICATION_EMAIL_RECIPIENT_V1 = 'notification:email:recipient:v1';
    public const NOTIFICATION_EMAIL_PAYLOAD_V1   = 'notification:email:payload:v1';

    /* ===============================
     * Identifiers (PII)
     * =============================== */

    public const IDENTIFIER_EMAIL_V1 = 'identifier:email:v1';
    public const IDENTIFIER_PHONE_V1 = 'identifier:phone:v1';

    /* ===============================
     * TOTP / MFA
     * =============================== */

    public const TOTP_SEED_V1 = 'totp:seed:v1';

    /* ===============================
     * Generic / System
     * =============================== */

    public const SYSTEM_SECRET_V1 = 'system:secret:v1';

    /* ===============================
     * QUEUE / EMAIL
     * =============================== */

    public const EMAIL_QUEUE_RECIPIENT_V1 = 'email:queue:recipient:v1';
    public const EMAIL_QUEUE_PAYLOAD_V1   = 'email:queue:payload:v1';

    /* ===============================
     * ABUSE PROTECTION
     * =============================== */

    /**
     * Context for signing abuse protection failure signals.
     *
     * Used for:
     * - Login failure counters
     * - Abuse mitigation cookies
     * - Stateless abuse signals
     *
     * IMPORTANT:
     * - HMAC / Signing only (NOT encryption)
     * - MUST remain stable once released
     * - MUST support key rotation
     */
    public const ABUSE_PROTECTION_SIGNAL_V1 = 'abuse:protection:signal:v1';


    private function __construct()
    {
        // Static registry only
    }
}
