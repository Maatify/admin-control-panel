<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\RecoveryStateService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


final readonly class RecoveryStateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RecoveryStateService $recoveryState
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Monitor state transitions on every request to ensure authoritative audit
        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }
        $this->recoveryState->monitorState($context);
        return $handler->handle($request);
    }
}
