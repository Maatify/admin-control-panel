<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HttpRequestTelemetryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly DiagnosticsTelemetryService $telemetryService
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $start = microtime(true);
        $response = null;

        try {
            $response = $handler->handle($request);
            return $response;
        } finally {
            try {
                $context = $request->getAttribute(RequestContext::class);

                if ($context instanceof RequestContext && $response instanceof ResponseInterface) {
                    $durationMs = (int) ((microtime(true) - $start) * 1000);

                    $metadata = [
                        'method'      => $request->getMethod(),
                        'route_name'  => $context->getRouteName(),
                        'status_code' => $response->getStatusCode(),
                        'duration_ms' => $durationMs,
                    ];

                    $adminContext = $request->getAttribute(AdminContext::class);

                    $actorType = 'SYSTEM';
                    $actorId = null;

                    if ($adminContext instanceof AdminContext) {
                        $actorType = 'ADMIN';
                        $actorId = $adminContext->adminId;
                    }

                    // Enrich metadata with request context since DiagnosticsTelemetryService
                    // is not request-aware by default.
                    $metadata['request_id'] = $context->requestId;
                    $metadata['ip_address'] = $context->ipAddress;
                    $metadata['user_agent'] = $context->userAgent;

                    $this->telemetryService->recordEvent(
                        'http_request_end',
                        'INFO',
                        $actorType,
                        $actorId,
                        $metadata,
                        $durationMs
                    );
                }
            } catch (Throwable) {
                // swallow — telemetry must never affect request flow
            }
        }
    }
}
