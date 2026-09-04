<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 00:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionsMetadataRepositoryInterface;
use Maatify\AdminKernel\Validation\Schemas\Permissions\PermissionMetadataUpdateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class PermissionMetadataUpdateController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private PermissionsMetadataRepositoryInterface $updater,

    ){}

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new PermissionMetadataUpdateSchema(), $body);

        /** @var array{
         *   display_name?: string,
         *   description?: string
         * } $body
         */

        // 2) Extract ID from route
        $permissionId = (int) $args['id'];

        // 3) Extract mutable fields
        $displayName  = $body['display_name'] ?? null;
        $description  = $body['description'] ?? null;

        // 4) Semantic guard: nothing to update
        if ($displayName === null && $description === null) {
            // لا نضيف ValidationError جديد — ده قرار دلالي
            return $response
                ->withStatus(204); // No Content (nothing changed)
        }

        // 5) Execute update
        $this->updater->updateMetadata(
            permissionId: $permissionId,
            displayName: $displayName !== null ? (string) $displayName : null,
            description: $description !== null ? (string) $description : null
        );

        // 6) Success (no payload needed)
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}