<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

class LocalFallbackLimiter
{
    private static array $counters = [];
    private static int $lastGc = 0;

    // Windows (Seconds)
    private const WINDOW_LOGIN = 600; // 10m
    private const WINDOW_OTP = 900;   // 15m
    private const WINDOW_API = 60;    // 1m

    // Caps
    private const DEGRADED_LOGIN_ACCOUNT = 3;
    private const DEGRADED_LOGIN_IP = 20;
    private const DEGRADED_OTP_ACCOUNT = 2;
    private const DEGRADED_OTP_IP = 10;
    private const API_IP = 120;
    private const API_IP_UA = 60;

    public static function check(string $policyName, string $mode, string $ip, ?string $accountId = null, string $ua = ''): bool
    {
        self::gc();

        $allowed = true;

        if ($mode === 'DEGRADED_MODE') {
            if ($policyName === 'login_protection') {
                $window = self::WINDOW_LOGIN;
                if ($accountId && !self::incrementAndCheck("deg:login:acc:{$accountId}", self::DEGRADED_LOGIN_ACCOUNT, $window)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:login:ip:{$ip}", self::DEGRADED_LOGIN_IP, $window)) {
                    $allowed = false;
                }
            } elseif ($policyName === 'otp_protection') {
                $window = self::WINDOW_OTP;
                if ($accountId && !self::incrementAndCheck("deg:otp:acc:{$accountId}", self::DEGRADED_OTP_ACCOUNT, $window)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:otp:ip:{$ip}", self::DEGRADED_OTP_IP, $window)) {
                    $allowed = false;
                }
            } elseif ($policyName === 'api_heavy_protection') {
                $window = self::WINDOW_API;
                if (!self::incrementAndCheck("fail:api:ip:{$ip}", self::API_IP, $window)) {
                    $allowed = false;
                }
                $k2 = md5("{$ip}:{$ua}");
                if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA, $window)) {
                    $allowed = false;
                }
            }
        } elseif ($mode === 'FAIL_OPEN' && $policyName === 'api_heavy_protection') {
             $window = self::WINDOW_API;
             if (!self::incrementAndCheck("fail:api:ip:{$ip}", self::API_IP, $window)) {
                 $allowed = false;
             }
             $k2 = md5("{$ip}:{$ua}");
             if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA, $window)) {
                 $allowed = false;
             }
        }

        return $allowed;
    }

    private static function incrementAndCheck(string $key, int $limit, int $window): bool
    {
        // Use time bucket for stateless window tracking
        $bucket = (int) floor(time() / $window);
        $bucketKey = "{$key}:{$bucket}";

        if (!isset(self::$counters[$bucketKey])) {
            self::$counters[$bucketKey] = 0;
        }
        self::$counters[$bucketKey]++;
        return self::$counters[$bucketKey] <= $limit;
    }

    private static function gc(): void
    {
        // Simple GC to prevent infinite array growth
        $now = time();
        if ($now - self::$lastGc > 3600) { // Every hour
            self::$counters = [];
            self::$lastGc = $now;
        }
    }
}
