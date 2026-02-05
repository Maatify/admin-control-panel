<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-05 10:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\AppSettings\Metadata\AppSettingsMetadataProvider;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AppSettingsMetadataController
{
    public function __construct(
        private AppSettingsMetadataProvider $provider
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $metadata = $this->provider->getMetadata();

        $response->getBody()->write(
            json_encode($metadata, JSON_THROW_ON_ERROR)
        );

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
