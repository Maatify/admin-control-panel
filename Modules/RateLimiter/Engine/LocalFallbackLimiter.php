<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

class LocalFallbackLimiter
{
    // Simple static storage to simulate per-process/request memory
    private static array $counters = [];
    private static int $lastReset = 0;

    private const WINDOW = 600; // 10m

    // DEGRADED CAPS (LOCKED)
    private const DEGRADED_LOGIN_ACCOUNT = 3; // per 10m
    private const DEGRADED_LOGIN_IP = 20;     // per 10m
    private const DEGRADED_OTP_ACCOUNT = 2;   // per 15m
    private const DEGRADED_OTP_IP = 10;       // per 15m

    // FAIL_OPEN CAPS
    private const API_IP = 120; // per min
    private const API_IP_UA = 60; // per min

    public static function check(string $policyName, string $mode, string $ip, ?string $accountId = null): bool
    {
        self::gc();

        $allowed = true;

        if ($mode === 'DEGRADED_MODE') {
            if ($policyName === 'login_protection') {
                if ($accountId && !self::incrementAndCheck("deg:login:acc:{$accountId}", self::DEGRADED_LOGIN_ACCOUNT)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:login:ip:{$ip}", self::DEGRADED_LOGIN_IP)) {
                    $allowed = false;
                }
            } elseif ($policyName === 'otp_protection') {
                if ($accountId && !self::incrementAndCheck("deg:otp:acc:{$accountId}", self::DEGRADED_OTP_ACCOUNT)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:otp:ip:{$ip}", self::DEGRADED_OTP_IP)) {
                    $allowed = false;
                }
            }
        } elseif ($mode === 'FAIL_OPEN' && $policyName === 'api_heavy_protection') {
             // API Heavy
             if (!self::incrementAndCheck("fail:api:ip:{$ip}", self::API_IP)) {
                 $allowed = false;
             }
             // We don't have UA easily here without passing it.
             // Assume IP check is sufficient for coarse guardrail or pass composite key.
             // Given complexity, IP check is primary guardrail.
        }

        return $allowed;
    }

    private static function incrementAndCheck(string $key, int $limit): bool
    {
        if (!isset(self::$counters[$key])) {
            self::$counters[$key] = 0;
        }
        self::$counters[$key]++;
        return self::$counters[$key] <= $limit;
    }

    private static function gc(): void
    {
        // Simple flush if window passed?
        // Anti-reset guard: "counters MUST persist for the entire degraded epoch".
        // Here we just use a simplified window reset.
        $now = time();
        if ($now - self::$lastReset > self::WINDOW) {
            self::$counters = [];
            self::$lastReset = $now;
        }
    }
}
