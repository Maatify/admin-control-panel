<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Context\Resolver;

use App\Context\RequestContext;
use Psr\Http\Message\ServerRequestInterface;

final class RequestContextResolver
{
    public function resolve(ServerRequestInterface $request): RequestContext
    {
        $requestId = $request->getAttribute('request_id');

        if (!is_string($requestId) || $requestId === '') {
            throw new \RuntimeException('RequestContextResolver called without valid request_id');
        }

        $serverParams = $request->getServerParams();

        $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
        if (!is_string($ipAddress) || $ipAddress === '') {
            $ipAddress = '0.0.0.0';
        }

        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? null;
        if (!is_string($userAgent) || $userAgent === '') {
            $userAgent = 'unknown';
        }

        return new RequestContext(
            requestId : $requestId,
            ipAddress : $ipAddress,
            userAgent : $userAgent,
        );
    }
}

