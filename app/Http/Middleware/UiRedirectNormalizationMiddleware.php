<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class UiRedirectNormalizationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // 1. Rewrite Redirects
        if ($response->hasHeader('Location')) {
            $location = $response->getHeaderLine('Location');
            $newLocation = $this->rewriteLocation($location);
            if ($newLocation !== $location) {
                return $response->withHeader('Location', $newLocation);
            }
        }

        // 2. Guard against JSON responses (e.g. from SessionStateGuardMiddleware)
        $contentType = $response->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $body = (string)$response->getBody();
            // Reset stream
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

            // Fallback for unparseable JSON or other JSON responses on UI routes
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/ui/error?code=backend_json_error');
        }

        return $response;
    }

    private function rewriteLocation(string $location): string
    {
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
