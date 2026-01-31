<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-31 12:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\Contract;

/**
 * CryptoContextProviderInterface
 *
 * Governs access to cryptographic context strings.
 *
 * - Prevents hardcoded context usage
 * - Centralizes context selection policy
 * - Allows different implementations per project or environment
 *
 * This interface is OPTIONAL and does not replace direct usage.
 *
 * Implementations are expected to return versioned, documented context strings
 * defined by the Crypto module.
 */
interface CryptoContextProviderInterface
{
    /**
     * Context for encrypting identifier emails (PII).
     *
     * Example: "identifier:email:v1"
     */
    public function identifierEmail(): string;

    /**
     * Context for encrypting identifier phone numbers (PII).
     *
     * Example: "identifier:phone:v1"
     */
    public function identifierPhone(): string;

    /**
     * Context for encrypting email recipient addresses
     * used in notification systems.
     *
     * Example: "notification:email:recipient:v1"
     */
    public function notificationEmailRecipient(): string;

    /**
     * Context for encrypting email payload/body content
     * used in notification systems.
     *
     * Example: "notification:email:payload:v1"
     */
    public function notificationEmailPayload(): string;

    /* ===============================
     * QUEUE / EMAIL
     * =============================== */

    /**
     * Context for encrypting email recipient addresses
     * stored inside email queue records.
     *
     * This context is intentionally separated from
     * runtime notification email contexts to ensure:
     * - Proper domain separation
     * - Independent key derivation
     * - Safer long-lived queued data
     *
     * Example: "email:queue:recipient:v1"
     */
    public function emailQueueRecipient(): string;

    /**
     * Context for encrypting email payload/body content
     * stored inside email queue records.
     *
     * This context MUST be different from notification
     * payload contexts due to different lifecycle
     * and storage characteristics.
     *
     * Example: "email:queue:payload:v1"
     */
    public function emailQueuePayload(): string;

    /* ===============================
     * MFA / SYSTEM
     * =============================== */

    /**
     * Context for encrypting TOTP / MFA secrets.
     *
     * Example: "totp:seed:v1"
     */
    public function totpSeed(): string;

    /**
     * Generic system-level secret encryption.
     *
     * Example: "system:secret:v1"
     */
    public function systemSecret(): string;
}
