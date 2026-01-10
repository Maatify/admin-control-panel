<?php

declare(strict_types=1);

namespace App\Modules\InputNormalization\Middleware;

use App\Modules\InputNormalization\Contracts\InputNormalizerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InputNormalizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly InputNormalizerInterface $normalizer
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Normalize Query Params (GET inputs)
        $queryParams = $request->getQueryParams();
        if (is_array($queryParams)) {
            /** @var array<string, mixed> $queryParams */
            $normalizedQuery = $this->normalizer->normalize($queryParams);
            $request = $request->withQueryParams($normalizedQuery);
        }

        // 2. Normalize Parsed Body (POST/PUT inputs)
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            /** @var array<string, mixed> $parsedBody */
            $normalizedBody = $this->normalizer->normalize($parsedBody);
            $request = $request->withParsedBody($normalizedBody);
        }

        return $handler->handle($request);
    }
}
