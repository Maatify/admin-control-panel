<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 23:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleToggleRepositoryInterface;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\Roles\RoleToggleSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RoleToggleController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private RoleToggleRepositoryInterface $repository
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request body
        $this->validationGuard->check(new RoleToggleSchema(), $body);

        /** @var array{
         *   is_active: bool
         * } $body
         */

        // 2) Extract role ID
        $roleId = (int) $args['id'];

        // 3) Extract desired state
        $isActive = (bool) $body['is_active'];

        // 4) Execute toggle
        $this->repository->toggle(
            roleId: $roleId,
            isActive: $isActive
        );

        // 5) Success — no payload
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
