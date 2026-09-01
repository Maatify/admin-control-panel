<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Kernel\DTO;

use RuntimeException;

final class AdminRuntimeConfigDTO
{
    /* ─────────────────────────────
     * Application
     * ───────────────────────────── */
    public string $appEnv;
    public bool $appDebug;
    public string $appTimezone;
    public string $appName;
    public string $adminUrl;

    /* ─────────────────────────────
     * Database
     * ───────────────────────────── */
    public string $dbHost;
    public string $dbName;
    public string $dbUser;
    public string $dbPassword;

    /* ─────────────────────────────
     * Security / Passwords
     * ───────────────────────────── */
    public string $emailBlindIndexKey;

    public string $passwordPeppers;
    public string $passwordActivePepperId;
    public string $passwordArgon2Options;

    /* ─────────────────────────────
     * Crypto
     * ───────────────────────────── */
    public string $cryptoKeysJson;
    public string $cryptoActiveKeyId;

    /* ─────────────────────────────
     * TOTP
     * ───────────────────────────── */
    public string $totpIssuer;
    public int $totpEnrollmentTtlSeconds;

    /* ─────────────────────────────
     * Mail
     * ───────────────────────────── */
    public string $mailHost;
    public int $mailPort;
    public string $mailUsername;
    public string $mailPassword;
    public string $mailFromAddress;
    public string $mailFromName;
    public ?string $mailEncryption;
    public int $mailTimeoutSeconds;
    public string $mailCharset;
    public int $mailDebugLevel;

    /* ─────────────────────────────
     * UI
     * ───────────────────────────── */
    public string $assetBaseUrl;
    public ?string $logoUrl;
    public ?string $hostTemplatePath;

    /* ─────────────────────────────
     * Flags
     * ───────────────────────────── */
    public bool $recoveryMode;

    /* ─────────────────────────────
     * Turnstile
     * ───────────────────────────── */
    public ?string $turnstileSiteKey;
    public ?string $turnstileSecretKey;

    /* ─────────────────────────────
     * HCaptcha
     * ───────────────────────────── */
    public ?string $hCaptchaSiteKey;
    public ?string $hCaptchaSecretKey;

    /* ─────────────────────────────
     * RecaptchaV2
     * ───────────────────────────── */
    public ?string $recaptchaV2SiteKey;
    public ?string $recaptchaV2SecretKey;

    /* ─────────────────────────────
     * Abuse Challenge Provider Selector
     * ───────────────────────────── */
    public string $abuseChallengeProvider;

    private function __construct() {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $self = new self();

        // Application
        $self->appEnv       = self::reqString($data, 'ADMIN_APP_ENV');
        $self->appDebug     = self::reqBool($data, 'ADMIN_APP_DEBUG');
        $self->appTimezone  = self::reqString($data, 'ADMIN_APP_TIMEZONE');
        $self->appName      = self::reqString($data, 'ADMIN_APP_NAME');
        $self->adminUrl     = self::reqString($data, 'ADMIN_URL');

        // Database
        $self->dbHost       = self::reqString($data, 'ADMIN_DB_HOST');
        $self->dbName       = self::reqString($data, 'ADMIN_DB_NAME');
        $self->dbUser       = self::reqString($data, 'ADMIN_DB_USER');
        $self->dbPassword   = self::reqString($data, 'ADMIN_DB_PASS');

        // Security / Password
        $self->emailBlindIndexKey        = self::reqString($data, 'ADMIN_EMAIL_BLIND_INDEX_KEY');
        $self->passwordPeppers           = self::reqString($data, 'ADMIN_PASSWORD_PEPPERS');
        $self->passwordActivePepperId    = self::reqString($data, 'ADMIN_PASSWORD_ACTIVE_PEPPER_ID');
        $self->passwordArgon2Options = self::optStringNotNull(
            $data,
            'ADMIN_PASSWORD_ARGON2_OPTIONS',
            '{"memory_cost":65536,"time_cost":4,"threads":1}'
        );

        // Crypto
        $self->cryptoKeysJson    = self::reqString($data, 'ADMIN_CRYPTO_KEYS');
        $self->cryptoActiveKeyId = self::reqString($data, 'ADMIN_CRYPTO_ACTIVE_KEY_ID');

        // TOTP
        $self->totpIssuer               = self::reqString($data, 'ADMIN_TOTP_ISSUER');
        $self->totpEnrollmentTtlSeconds = self::reqInt($data, 'ADMIN_TOTP_ENROLLMENT_TTL_SECONDS');

        // Mail
        $self->mailHost        = self::reqString($data, 'ADMIN_MAIL_HOST');
        $self->mailPort        = self::reqInt($data, 'ADMIN_MAIL_PORT');
        $self->mailUsername    = self::reqString($data, 'ADMIN_MAIL_USERNAME');
        $self->mailPassword    = self::reqString($data, 'ADMIN_MAIL_PASSWORD');
        $self->mailFromAddress = self::reqString($data, 'ADMIN_MAIL_FROM_ADDRESS');
        $self->mailFromName    = self::reqString($data, 'ADMIN_MAIL_FROM_NAME');
        $self->mailEncryption  = self::optString($data, 'ADMIN_MAIL_ENCRYPTION');
        $self->mailTimeoutSeconds = self::optInt($data, 'ADMIN_MAIL_TIMEOUT_SECONDS', 10);
        $self->mailCharset        = self::optStringNotNull($data, 'ADMIN_MAIL_CHARSET', 'UTF-8');
        $self->mailDebugLevel     = self::optInt($data, 'ADMIN_MAIL_DEBUG_LEVEL', 0);

        // UI
        $self->assetBaseUrl     = self::optStringNotNull($data, 'ADMIN_ASSET_BASE_URL', '/');
        $self->logoUrl          = self::optString($data, 'ADMIN_LOGO_URL');
        $self->hostTemplatePath = self::optString($data, 'ADMIN_HOST_TEMPLATE_PATH');

        // Flags
        $self->recoveryMode = self::optBool($data, 'ADMIN_RECOVERY_MODE', false);

        // Turnstile
        $self->turnstileSiteKey = self::optString($data, 'ADMIN_TURNSTILE_SITE_KEY', '');
        $self->turnstileSecretKey = self::optString($data, 'ADMIN_TURNSTILE_SECRET_KEY', '');

        // HCaptcha
        $self->hCaptchaSiteKey = self::optString($data, 'ADMIN_HCAPTCHA_SITE_KEY', '');
        $self->hCaptchaSecretKey = self::optString($data, 'ADMIN_HCAPTCHA_SECRET_KEY', '');

        // RecaptchaV2
        $self->recaptchaV2SiteKey = self::optString($data, 'ADMIN_RECAPTCHA_V2_SITE_KEY', '');
        $self->recaptchaV2SecretKey = self::optString($data, 'ADMIN_RECAPTCHA_V2_SECRET_KEY', '');

        // Abuse Challenge Provider Selector
        $self->abuseChallengeProvider = self::optStringNotNull($data, 'ADMIN_ABUSE_CHALLENGE_PROVIDER', 'none');

        return $self;
    }


    /* ─────────────────────────────
     * Helpers
     * ───────────────────────────── */
    /**
     * @param array<string, mixed> $data
     */
    private static function reqString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
            throw new RuntimeException("Missing or invalid config key: {$key}");
        }
        return $data[$key];
    }
    /**
     * @param array<string, mixed> $data
     */
    private static function optString(array $data, string $key, ?string $default = null): ?string
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            return $default;
        }

        $value = trim($data[$key]);
        return $value !== '' ? $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function reqBool(array $data, string $key): bool
    {
        if (!isset($data[$key])) {
            throw new RuntimeException("Missing config key: {$key}");
        }
        $value = filter_var($data[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new RuntimeException("Invalid boolean config key: {$key}");
        }
        return $value;
    }
    /**
     * @param array<string, mixed> $data
     */
    private static function optStringNotNull(
        array $data,
        string $key,
        string $default
    ): string {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];

        if (!is_scalar($value)) {
            return $default;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optBool(array $data, string $key, bool $default): bool
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = filter_var($data[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        return $value ?? $default;
    }
    /**
     * @param array<string, mixed> $data
     */
    private static function reqInt(array $data, string $key): int
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) {
            throw new RuntimeException("Missing or invalid int config key: {$key}");
        }
        return (int)$data[$key];
    }
    /**
     * @param array<string, mixed> $data
     */
    private static function optInt(array $data, string $key, int $default): int
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) {
            return $default;
        }
        return (int)$data[$key];
    }
}
