<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminManagementInterface;
use App\Domain\DTO\Admin\AdminCreateRequestDTO;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminCreateController
{
    public function __construct(
        private AdminManagementInterface $adminManagement,
        private AuthorizationService $authorizationService
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            assert(is_int($adminId));

            $this->authorizationService->checkPermission($adminId, 'admins.create');

            $body = $request->getParsedBody();
            if (!is_array($body)) {
                $body = []; // DTO validation will fail if required fields missing
            }

            $dto = AdminCreateRequestDTO::fromArray($body);

            $token = $request->getCookieParams()['auth_token'] ?? '';
            if (!is_string($token)) {
                 $token = '';
            }

            if ($token === '') {
                 throw new \RuntimeException("Missing auth token");
            }
            // Pass RAW token to Service, which handles hashing for Audit/Repo
            $newAdminId = $this->adminManagement->createAdmin($dto, $adminId, $token);

            $response->getBody()->write(json_encode(['id' => $newAdminId], JSON_THROW_ON_ERROR));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $status = $e instanceof \App\Domain\Exception\PermissionDeniedException ? 403 : 500;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }
}
