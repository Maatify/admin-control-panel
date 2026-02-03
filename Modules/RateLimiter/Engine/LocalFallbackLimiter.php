<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

class LocalFallbackLimiter
{
    /** @var array<string, int> */
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

        // Normalize IP (IPv6 /64)
        $normalizedIp = self::getIpPrefix($ip);

        // Normalize UA (Major Version + Platform)
        // Simplified fallback normalization to avoid dependency on heavy parser
        $normalizedUa = self::normalizeUa($ua);

        if ($mode === 'DEGRADED_MODE') {
            if ($policyName === 'login_protection') {
                $window = self::WINDOW_LOGIN;
                if ($accountId && !self::incrementAndCheck("deg:login:acc:{$accountId}", self::DEGRADED_LOGIN_ACCOUNT, $window)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:login:ip:{$normalizedIp}", self::DEGRADED_LOGIN_IP, $window)) {
                    $allowed = false;
                }
            } elseif ($policyName === 'otp_protection') {
                $window = self::WINDOW_OTP;
                if ($accountId && !self::incrementAndCheck("deg:otp:acc:{$accountId}", self::DEGRADED_OTP_ACCOUNT, $window)) {
                    $allowed = false;
                }
                if (!self::incrementAndCheck("deg:otp:ip:{$normalizedIp}", self::DEGRADED_OTP_IP, $window)) {
                    $allowed = false;
                }
            } elseif ($policyName === 'api_heavy_protection') {
                $window = self::WINDOW_API;
                if (!self::incrementAndCheck("fail:api:ip:{$normalizedIp}", self::API_IP, $window)) {
                    $allowed = false;
                }
                $k2 = md5("{$normalizedIp}:{$normalizedUa}");
                if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA, $window)) {
                    $allowed = false;
                }
            }
        } elseif ($mode === 'FAIL_OPEN' && $policyName === 'api_heavy_protection') {
             $window = self::WINDOW_API;
             if (!self::incrementAndCheck("fail:api:ip:{$normalizedIp}", self::API_IP, $window)) {
                 $allowed = false;
             }
             $k2 = md5("{$normalizedIp}:{$normalizedUa}");
             if (!self::incrementAndCheck("fail:api:k2:{$k2}", self::API_IP_UA, $window)) {
                 $allowed = false;
             }
        }

        return $allowed;
    }

    private static function getIpPrefix(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                 $hex = bin2hex($packed);
                 return substr($hex, 0, 16); // /64
            }
        }
        return $ip;
    }

    private static function normalizeUa(string $ua): string
    {
        // Minimal coarse normalization for fallback
        // Format: [Browser]/[Major] ([OS])

        $browser = 'Other';
        $major = '0';
        $os = 'Unknown';

        if (preg_match('#(Firefox|Chrome|Safari|Edge)/([0-9]+)#', $ua, $matches)) {
            $browser = $matches[1];
            $major = $matches[2];
        }

        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Mac OS')) $os = 'MacOS';
        elseif (str_contains($ua, 'Linux')) $os = 'Linux';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'iOS') || str_contains($ua, 'iPhone')) $os = 'iOS';

        return "{$browser}/{$major} ({$os})";
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
