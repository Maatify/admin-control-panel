<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminManagementInterface;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminDisableController
{
    public function __construct(
        private AdminManagementInterface $adminManagement,
        private AuthorizationService $authorizationService
    ) {}

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        try {
            $actorId = $request->getAttribute('admin_id');
            assert(is_int($actorId));

            $targetAdminId = (int)($args['id'] ?? 0);

            $this->authorizationService->checkPermission($actorId, 'admins.disable');

            // Self-disable check
            if ($actorId === $targetAdminId) {
                $response->getBody()->write(json_encode(['error' => 'Cannot disable yourself.'], JSON_THROW_ON_ERROR));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $this->adminManagement->disableAdmin($targetAdminId, $actorId);

            return $response->withStatus(204);
        } catch (\Throwable $e) {
            $status = $e instanceof \App\Domain\Exception\PermissionDeniedException ? 403 : 500;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }
}
