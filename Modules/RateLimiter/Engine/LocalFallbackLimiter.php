<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

class LocalFallbackLimiter
{
    private static array $counters = [];
    private static int $lastReset = 0;

    private const WINDOW = 600; // 10m

    // DEGRADED CAPS (LOCKED)
    private const DEGRADED_LOGIN_ACCOUNT = 3;
    private const DEGRADED_LOGIN_IP = 20;
    private const DEGRADED_OTP_ACCOUNT = 2;
    private const DEGRADED_OTP_IP = 10;

    // FAIL_OPEN CAPS
    private const API_IP = 120; // per min
    private const API_IP_UA = 60; // per min

    public static function check(string $policyName, string $mode, string $ip, ?string $accountId = null, string $ua = ''): bool
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
            } elseif ($policyName === 'api_heavy_protection') {
                // API Heavy in Degraded Mode (via Circuit Breaker)
                // "Even in FAIL_OPEN (or Degraded equivalent for API), apply per-node coarse throttles"
                if (!self::incrementAndCheck("fail:api:ip:{$ip}", self::API_IP)) {
                    $allowed = false;
                }
                $k2 = md5("{$ip}:{$ua}");
                if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA)) {
                    $allowed = false;
                }
            }
        } elseif ($mode === 'FAIL_OPEN' && $policyName === 'api_heavy_protection') {
             // API Heavy (FAIL_OPEN)
             if (!self::incrementAndCheck("fail:api:ip:{$ip}", self::API_IP)) {
                 $allowed = false;
             }
             // Check K2 (IP+UA)
             $k2 = md5("{$ip}:{$ua}");
             if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA)) {
                 $allowed = false;
             }
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
        $now = time();
        if ($now - self::$lastReset > self::WINDOW) {
            self::$counters = [];
            self::$lastReset = $now;
        }
    }
}
