<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsWriterRepositoryInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\Permissions\DirectPermissionRevokeSchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RevokeDirectPermissionController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private DirectPermissionsWriterRepositoryInterface $writer
    ) {
    }

    /**
     * =========================================
     * Revoke Direct Permission
     * POST /admins/{admin_id}/permissions/direct/revoke
     * =========================================
     *
     * @param array<string,string> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminId = (int) $args['admin_id'];

        /** @var array{permission_id:int} $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new DirectPermissionRevokeSchema(),
            $body
        );

        $this->writer->revokeDirectPermission(
            adminId: $adminId,
            permissionId: (int) $body['permission_id']
        );

        return $response->withStatus(204);
    }
}
