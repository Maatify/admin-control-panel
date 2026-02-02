<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-31 12:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Crypto;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;

/**
 * AdminCryptoContextProvider
 *
 * Project-specific implementation of CryptoContextProviderInterface.
 *
 * - Delegates to the project's CryptoContext registry
 * - Owns all cryptographic context semantics
 */
final class AdminCryptoContextProvider implements CryptoContextProviderInterface
{
    public function identifierEmail(): string
    {
        return CryptoContext::IDENTIFIER_EMAIL_V1;
    }

    public function identifierPhone(): string
    {
        return CryptoContext::IDENTIFIER_PHONE_V1;
    }

    public function notificationEmailRecipient(): string
    {
        return CryptoContext::NOTIFICATION_EMAIL_RECIPIENT_V1;
    }

    public function notificationEmailPayload(): string
    {
        return CryptoContext::NOTIFICATION_EMAIL_PAYLOAD_V1;
    }

    public function totpSeed(): string
    {
        return CryptoContext::TOTP_SEED_V1;
    }

    public function systemSecret(): string
    {
        return CryptoContext::SYSTEM_SECRET_V1;
    }

    public function emailQueueRecipient(): string
    {
        return CryptoContext::EMAIL_QUEUE_RECIPIENT_V1;
    }

    public function emailQueuePayload(): string
    {
        return CryptoContext::EMAIL_QUEUE_PAYLOAD_V1;
    }

    public function abuseProtection(): string
    {
        return CryptoContext::ABUSE_PROTECTION_SIGNAL_V1;
    }
}
