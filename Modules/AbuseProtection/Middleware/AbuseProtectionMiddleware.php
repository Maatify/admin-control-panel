<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Middleware;

use Maatify\AbuseProtection\Contracts\AbuseDecisionInterface;
use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class AbuseProtectionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AbuseDecisionInterface $policy,
        private ChallengeProviderInterface $provider
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        // TODO [RateLimiter]:
        // Populate 'login_failures' attribute from RateLimiter middleware
        // before AbuseProtectionMiddleware runs.

        $failures = $request->getAttribute('login_failures');
        $failureCount = is_int($failures) ? $failures : 0;

        $context = new AbuseContextDTO(
            route        : $request->getUri()->getPath(),
            method       : $request->getMethod(),
            ipAddress    : $request->getServerParams()['REMOTE_ADDR'] ?? null,
            userAgent    : $request->getHeaderLine('User-Agent') ?: null,
            failureCount : $failureCount
        );

        // Is challenge required at all?
        if (! $this->policy->requiresChallenge($context)) {
            return $handler->handle($request);
        }

        // Signal UI & controller
        $request = $request->withAttribute('require_challenge', true);

        // Try verification only if token exists
        $payload = (array) $request->getParsedBody();
        $result  = $this->provider->verify($context, $payload);

        $request = $request
            ->withAttribute('challenge_passed', $result->passed)
            ->withAttribute(
                'challenge_reason',
                $result->reason ?? 'challenge_required'
            );

        return $handler->handle($request);
    }
}
