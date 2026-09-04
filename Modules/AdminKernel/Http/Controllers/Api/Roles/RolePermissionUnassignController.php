<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface;
use Maatify\AdminKernel\Validation\Schemas\Roles\RolePermissionUnassignSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RolePermissionUnassignController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private RolePermissionsRepositoryInterface $repository
    )
    {
    }

    /**
     * @param   array<string,string>  $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(
            new RolePermissionUnassignSchema(),
            $body
        );

        $roleId = (int)$args['id'];

        /**
         * @var array{
         *   permission_id:int
         * } $body
         */
        $permissionId = (int)$body['permission_id'];

        // 2) Unassign
        $this->repository->unassign($roleId, $permissionId);

        // 3) Success (no payload)
        return $response->withStatus(204);
    }
}
