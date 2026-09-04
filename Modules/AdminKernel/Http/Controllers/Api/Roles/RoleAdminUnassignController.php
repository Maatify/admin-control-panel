<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface;
use Maatify\AdminKernel\Validation\Schemas\Roles\RoleAdminUnassignSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RoleAdminUnassignController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private RoleAdminsRepositoryInterface $repository
    )
    {
    }

    /**
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        $this->validationGuard->check(new RoleAdminUnassignSchema(), $body);

        /**
         * @var array{admin_id:int} $body
         */
        $adminId = $body['admin_id'];
        $roleId = (int)$args['id'];

        $this->repository->unassign($roleId, $adminId);

        return $response->withStatus(204);
    }
}
