<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface;
use Maatify\AdminKernel\Validation\Schemas\Roles\RoleAdminAssignSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RoleAdminAssignController
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

        $this->validationGuard->check(new RoleAdminAssignSchema(), $body);

        /**
         * @var array{admin_id:int} $body
         */
        $adminId = $body['admin_id'];
        $roleId = (int)$args['id'];

        $this->repository->assign($roleId, $adminId);

        return $response->withStatus(204);
    }
}
