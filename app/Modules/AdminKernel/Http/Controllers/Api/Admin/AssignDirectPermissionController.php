<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsWriterRepositoryInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\Permissions\DirectPermissionAssignSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AssignDirectPermissionController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private DirectPermissionsWriterRepositoryInterface $writer
    ) {
    }

    /**
     * =========================================
     * Assign Direct Permission
     * POST /admins/{id}/permissions/direct/assign
     * =========================================
     *
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) validate via schema (canonical style)
        $this->validationGuard->check(
            new DirectPermissionAssignSchema(),
            $body
        );

        /**
         * @var array{
         *   permission_id:int,
         *   is_allowed:bool,
         *   expires_at?:string|null
         * } $body
         */
        $adminId      = (int) $args['admin_id'];
        $permissionId = (int) $body['permission_id'];
        $isAllowed    = (bool) $body['is_allowed'];
        $expiresAt    = $body['expires_at'] ?? null;

        // 2) command execution
        $this->writer->assignDirectPermission(
            adminId: $adminId,
            permissionId: $permissionId,
            isAllowed: $isAllowed,
            expiresAt: $expiresAt
        );

        // 3) command response
        return $response->withStatus(204);
    }
}
