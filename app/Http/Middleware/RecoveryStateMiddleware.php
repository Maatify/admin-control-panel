<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\RecoveryStateService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecoveryStateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RecoveryStateService
    ) {
    }

    public function process(ServerRequestInterface , RequestHandlerInterface ): ResponseInterface
    {
        // Monitor state transitions on every request to ensure authoritative audit
        ->recoveryStateService->monitorState();

        return ->handle();
    }
}
