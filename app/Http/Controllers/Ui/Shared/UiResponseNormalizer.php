<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui\Shared;

use Psr\Http\Message\ResponseInterface;

class UiResponseNormalizer
{
    /**
     * Normalizes backend responses for the UI layer:
     * 1. Rewrites redirects to /ui/* namespace.
     * 2. Detects JSON responses (errors) and converts them to UI redirects.
     */
    public static function normalize(ResponseInterface $response): ResponseInterface
    {
        // 1. Rewrite Redirects
        if ($response->hasHeader('Location')) {
            $location = $response->getHeaderLine('Location');
            $newLocation = self::rewriteLocation($location);
            if ($newLocation !== $location) {
                return $response->withHeader('Location', $newLocation);
            }
        }

        // 2. Guard against JSON responses
        $contentType = $response->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            // We need to parse the JSON to understand the intent,
            // or default to a generic error page if parsing fails.
            $body = (string)$response->getBody();
            // Reset stream for safety if further used (though we replace it)
            $response->getBody()->rewind();

            $data = json_decode($body, true);
            if (is_array($data)) {
                // Check for specific error codes requiring redirection
                if (isset($data['code']) && $data['code'] === 'STEP_UP_REQUIRED') {
                    return $response
                        ->withStatus(302)
                        ->withHeader('Location', '/ui/2fa/verify');
                }

                // Generic error handling -> /ui/error
                $errorCode = $data['code'] ?? 'unknown_error';
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/ui/error?code=' . urlencode((string)$errorCode));
            }

            // Fallback for unparseable JSON
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/ui/error?code=backend_json_error');
        }

        return $response;
    }

    private static function rewriteLocation(string $location): string
    {
        // Parse the URL to handle query parameters correctly if needed.
        // For simplicity, we'll use string replacement for known paths.
        // We must be careful not to rewrite external URLs (though rare here).

        $map = [
            '/login'        => '/ui/login',
            '/dashboard'    => '/ui/dashboard',
            '/verify-email' => '/ui/verify-email',
            '/2fa/verify'   => '/ui/2fa/verify',
            '/error'        => '/ui/error',
        ];

        foreach ($map as $backend => $ui) {
            // Exact match
            if ($location === $backend) {
                return $ui;
            }
            // Prefix match (e.g. /verify-email?...)
            if (str_starts_with($location, $backend . '?') || str_starts_with($location, $backend . '/')) {
                return $ui . substr($location, strlen($backend));
            }
        }

        return $location;
    }
}
