<?php

declare(strict_types=1);

namespace Maatify\AdminControlPanel\Bootstrap;

use RuntimeException;

/**
 * Adapt the Admin Control Panel's namespaced environment to the AdminKernel
 * module's reusable contracts without writing generic keys back to the
 * process environment.
 *
 * @phpstan-type Environment array<string, mixed>
 */
final class AdminEnvironmentAdapter
{
    /**
     * @var array<string, string>
     */
    private const ADMIN_KERNEL_KEYS = [
        'ADMIN_APP_ENV' => 'APP_ENV',
        'ADMIN_APP_DEBUG' => 'APP_DEBUG',
        'ADMIN_APP_TIMEZONE' => 'APP_TIMEZONE',
        'ADMIN_APP_NAME' => 'APP_NAME',
        'ADMIN_URL' => 'ADMIN_URL',
        'ADMIN_EMAIL_BLIND_INDEX_KEY' => 'EMAIL_BLIND_INDEX_KEY',
        'ADMIN_PASSWORD_PEPPERS' => 'PASSWORD_PEPPERS',
        'ADMIN_PASSWORD_ACTIVE_PEPPER_ID' => 'PASSWORD_ACTIVE_PEPPER_ID',
        'ADMIN_PASSWORD_ARGON2_OPTIONS' => 'PASSWORD_ARGON2_OPTIONS',
        'ADMIN_CRYPTO_KEYS' => 'CRYPTO_KEYS',
        'ADMIN_CRYPTO_ACTIVE_KEY_ID' => 'CRYPTO_ACTIVE_KEY_ID',
        'ADMIN_TOTP_ISSUER' => 'TOTP_ISSUER',
        'ADMIN_TOTP_ENROLLMENT_TTL_SECONDS' => 'TOTP_ENROLLMENT_TTL_SECONDS',
        'ADMIN_MAIL_HOST' => 'MAIL_HOST',
        'ADMIN_MAIL_PORT' => 'MAIL_PORT',
        'ADMIN_MAIL_USERNAME' => 'MAIL_USERNAME',
        'ADMIN_MAIL_PASSWORD' => 'MAIL_PASSWORD',
        'ADMIN_MAIL_FROM_ADDRESS' => 'MAIL_FROM_ADDRESS',
        'ADMIN_MAIL_FROM_NAME' => 'MAIL_FROM_NAME',
        'ADMIN_MAIL_ENCRYPTION' => 'MAIL_ENCRYPTION',
        'ADMIN_MAIL_TIMEOUT_SECONDS' => 'MAIL_TIMEOUT_SECONDS',
        'ADMIN_MAIL_CHARSET' => 'MAIL_CHARSET',
        'ADMIN_MAIL_DEBUG_LEVEL' => 'MAIL_DEBUG_LEVEL',
        'ADMIN_ASSET_BASE_URL' => 'ASSET_BASE_URL',
        'ADMIN_LOGO_URL' => 'LOGO_URL',
        'ADMIN_HOST_TEMPLATE_PATH' => 'HOST_TEMPLATE_PATH',
        'ADMIN_RECOVERY_MODE' => 'RECOVERY_MODE',
        'ADMIN_TURNSTILE_SITE_KEY' => 'TURNSTILE_SITE_KEY',
        'ADMIN_TURNSTILE_SECRET_KEY' => 'TURNSTILE_SECRET_KEY',
        'ADMIN_HCAPTCHA_SITE_KEY' => 'HCAPTCHA_SITE_KEY',
        'ADMIN_HCAPTCHA_SECRET_KEY' => 'HCAPTCHA_SECRET_KEY',
        'ADMIN_RECAPTCHA_V2_SITE_KEY' => 'RECAPTCHA_V2_SITE_KEY',
        'ADMIN_RECAPTCHA_V2_SECRET_KEY' => 'RECAPTCHA_V2_SECRET_KEY',
        'ADMIN_ABUSE_CHALLENGE_PROVIDER' => 'ABUSE_CHALLENGE_PROVIDER',
        'ADMIN_DB_HOST' => 'DB_HOST',
        'ADMIN_DB_NAME' => 'DB_NAME',
        'ADMIN_DB_USER' => 'DB_USER',
        'ADMIN_DB_PASS' => 'DB_PASS',
        'ADMIN_LOG_PATH' => 'LOG_PATH',
        'ADMIN_ASSETS_CDN_URL' => 'ASSETS_CDN_URL',
        'ADMIN_CDN_IMAGE_URL' => 'CDN_IMAGE_URL',
        'ADMIN_ASSET_VERSION' => 'ASSET_VERSION',
    ];

    /**
     * @param array<string, mixed> $adminEnv
     * @return array<string, string>
     */
    public static function forAdminKernel(array $adminEnv): array
    {
        $mapped = [];

        foreach (self::ADMIN_KERNEL_KEYS as $adminKey => $contractKey) {
            if (!array_key_exists($adminKey, $adminEnv)) {
                continue;
            }

            $value = $adminEnv[$adminKey];
            if (!is_string($value)) {
                throw new RuntimeException("Environment key {$adminKey} must be a string.");
            }

            $mapped[$contractKey] = $value;
        }

        return $mapped;
    }
}
