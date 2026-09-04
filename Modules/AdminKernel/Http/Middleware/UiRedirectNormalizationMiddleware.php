<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class UiRedirectNormalizationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // Guard against JSON responses (e.g. from SessionStateGuardMiddleware)
        $contentType = $response->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $body = (string)$response->getBody();
            // Reset stream
            $response->getBody()->rewind();

            $data = json_decode($body, true);
            if (is_array($data)) {
//                // Check for specific error codes requiring redirection
//                if (isset($data['code']) && $data['code'] === 'STEP_UP_REQUIRED') {
//                    return $response
//                        ->withStatus(302)
//                        ->withHeader('Location', '/2fa/verify');
//                }
                if (
                    isset($data['code'])
                    && $data['code'] === 'STEP_UP_REQUIRED'
                    && ($data['scope'] ?? null) !== 'login'
                ) {
                    return $response
                        ->withStatus(302)
                        ->withHeader('Location', '/2fa/verify');
                }

                // Generic error handling -> /error
                $errorCode = $data['code'] ?? 'unknown_error';
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/error?code=' . urlencode((string)$errorCode));
            }

            // Fallback for unparseable JSON or other JSON responses on UI routes
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/error?code=backend_json_error');
        }

        return $response;
    }
}
