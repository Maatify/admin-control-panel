<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\Validation\I18nScopeCreateSchema;
use Maatify\AdminKernel\Domain\I18n\Service\I18nScopeCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeCreateController
{
    public function __construct(
        private I18nScopeCommandService $writer,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new I18nScopeCreateSchema(), $body);

        $code = is_string($body['code'] ?? null) ? $body['code'] : '';
        $name = is_string($body['name'] ?? null) ? $body['name'] : '';

        $description = is_string($body['description'] ?? null)
            ? $body['description']
            : '';

        $isActive = isset($body['is_active']) && is_bool($body['is_active'])
            ? $body['is_active']
            : true;

        // duplicate codes are classified by the I18n package (DB UNIQUE is the race authority)
        $id = $this->writer->create($code, $name, $description, $isActive);

        $response->getBody()->write(json_encode([
            'id' => $id,
        ], JSON_THROW_ON_ERROR));

        // NOTE:
        // This endpoint intentionally returns HTTP 200 (not 201)
        // to stay consistent with the project's unified API response policy.
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
