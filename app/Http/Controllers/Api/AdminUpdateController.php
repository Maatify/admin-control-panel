<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminManagementInterface;
use App\Domain\DTO\Admin\AdminUpdateRequestDTO;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminUpdateController
{
    public function __construct(
        private AdminManagementInterface $adminManagement,
        private AuthorizationService $authorizationService
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $actorId = $request->getAttribute('admin_id');
        assert(is_int($actorId));

        $targetAdminId = (int)$args['id'];

        $this->authorizationService->checkPermission($actorId, 'admins.edit');

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $dto = AdminUpdateRequestDTO::fromArray($body);

        $token = $request->getCookieParams()['auth_token'] ?? '';
        $sessionId = hash('sha256', $token);

        $this->adminManagement->updateAdmin($targetAdminId, $dto, $actorId, $sessionId);

        return $response->withStatus(204);
    }
}
