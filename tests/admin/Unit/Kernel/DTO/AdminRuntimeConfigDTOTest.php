<?php

declare(strict_types=1);

namespace Tests\Unit\Kernel\DTO;

use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\AdminKernel\Bootstrap\AdminEnvironmentAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminRuntimeConfigDTOTest extends TestCase
{
    public function testReadsTheAdminNamespacedEnvironmentContract(): void
    {
        $config = AdminRuntimeConfigDTO::fromArray(
            AdminEnvironmentAdapter::forAdminKernel($this->validAdminEnvironment())
        );

        self::assertSame('testing', $config->appEnv);
        self::assertTrue($config->appDebug);
        self::assertSame('Africa/Cairo', $config->appTimezone);
        self::assertSame('Admin Test', $config->appName);
        self::assertSame('https://admin.test', $config->adminUrl);
        self::assertSame('storage/logs/admin', $config->logPath);
        self::assertSame('127.0.0.1', $config->dbHost);
        self::assertSame('admin_test', $config->dbName);
        self::assertSame('admin', $config->dbUser);
        self::assertSame('blind-index-key', $config->emailBlindIndexKey);
        self::assertSame('v1', $config->cryptoActiveKeyId);
        self::assertSame('p1', $config->passwordActivePepperId);
        self::assertSame('Admin Test', $config->totpIssuer);
        self::assertSame(600, $config->totpEnrollmentTtlSeconds);
        self::assertSame('smtp.test', $config->mailHost);
        self::assertSame(587, $config->mailPort);
        self::assertSame('/', $config->assetBaseUrl);
        self::assertSame('turnstile', $config->abuseChallengeProvider);
    }

    public function testDoesNotFallbackToLegacyApplicationEnvironmentKey(): void
    {
        $environment = $this->validAdminEnvironment();
        unset($environment['ADMIN_APP_ENV']);
        $environment['APP_ENV'] = 'testing';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_ENV');

        AdminRuntimeConfigDTO::fromArray(
            AdminEnvironmentAdapter::forAdminKernel($environment)
        );
    }

    /**
     * @return array<string, string>
     */
    private function validAdminEnvironment(): array
    {
        return [
            'ADMIN_APP_ENV' => 'testing',
            'ADMIN_APP_DEBUG' => 'true',
            'ADMIN_APP_TIMEZONE' => 'Africa/Cairo',
            'ADMIN_APP_NAME' => 'Admin Test',
            'ADMIN_URL' => 'https://admin.test',
            'ADMIN_DB_HOST' => '127.0.0.1',
            'ADMIN_DB_NAME' => 'admin_test',
            'ADMIN_DB_USER' => 'admin',
            'ADMIN_DB_PASS' => 'secret',
            'ADMIN_LOG_PATH' => 'storage/logs/admin',
            'ADMIN_EMAIL_BLIND_INDEX_KEY' => 'blind-index-key',
            'ADMIN_PASSWORD_PEPPERS' => '{"p1":"pepper"}',
            'ADMIN_PASSWORD_ACTIVE_PEPPER_ID' => 'p1',
            'ADMIN_PASSWORD_ARGON2_OPTIONS' => '{"memory_cost":1024,"time_cost":2,"threads":1}',
            'ADMIN_CRYPTO_KEYS' => '[{"id":"v1","key":"key"}]',
            'ADMIN_CRYPTO_ACTIVE_KEY_ID' => 'v1',
            'ADMIN_TOTP_ISSUER' => 'Admin Test',
            'ADMIN_TOTP_ENROLLMENT_TTL_SECONDS' => '600',
            'ADMIN_MAIL_HOST' => 'smtp.test',
            'ADMIN_MAIL_PORT' => '587',
            'ADMIN_MAIL_USERNAME' => 'admin',
            'ADMIN_MAIL_PASSWORD' => 'secret',
            'ADMIN_MAIL_FROM_ADDRESS' => 'admin@test',
            'ADMIN_MAIL_FROM_NAME' => 'Admin Test',
            'ADMIN_MAIL_ENCRYPTION' => 'tls',
            'ADMIN_MAIL_TIMEOUT_SECONDS' => '10',
            'ADMIN_MAIL_CHARSET' => 'UTF-8',
            'ADMIN_MAIL_DEBUG_LEVEL' => '0',
            'ADMIN_ASSET_BASE_URL' => '/',
            'ADMIN_LOGO_URL' => '',
            'ADMIN_HOST_TEMPLATE_PATH' => '',
            'ADMIN_RECOVERY_MODE' => 'false',
            'ADMIN_TURNSTILE_SITE_KEY' => 'site-key',
            'ADMIN_TURNSTILE_SECRET_KEY' => 'secret-key',
            'ADMIN_HCAPTCHA_SITE_KEY' => '',
            'ADMIN_HCAPTCHA_SECRET_KEY' => '',
            'ADMIN_RECAPTCHA_V2_SITE_KEY' => '',
            'ADMIN_RECAPTCHA_V2_SECRET_KEY' => '',
            'ADMIN_ABUSE_CHALLENGE_PROVIDER' => 'turnstile',
        ];
    }
}
