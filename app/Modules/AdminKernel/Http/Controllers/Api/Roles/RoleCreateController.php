<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 00:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleCreateRepositoryInterface;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\Roles\RoleCreateSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RoleCreateController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private RoleCreateRepositoryInterface $repository
    ) {
    }

    /**
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request body
        $this->validationGuard->check(new RoleCreateSchema(), $body);

        /** @var array{
         *   name: string,
         *   display_name?: string,
         *   description?: string
         * } $body
         */

        // 2) Execute creation
        $this->repository->create(
            name: $body['name'],
            displayName: $body['display_name'] ?? null,
            description: $body['description'] ?? null
        );

        // 3) Success — no payload
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
