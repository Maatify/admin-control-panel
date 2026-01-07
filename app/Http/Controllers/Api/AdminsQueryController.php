<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminQueryReaderInterface;
use App\Domain\DTO\Admin\AdminListQueryDTO;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminsQueryController
{
    public function __construct(
        private AdminQueryReaderInterface $reader,
        private AuthorizationService $authorizationService
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            assert(is_int($adminId));

            $this->authorizationService->checkPermission($adminId, 'admins.list');

            $body = $request->getParsedBody();
            if (!is_array($body)) {
                $body = [];
            }

            $query = AdminListQueryDTO::fromArray($body);
            $result = $this->reader->getAdmins($query);

            $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $status = $e instanceof \App\Domain\Exception\PermissionDeniedException ? 403 : 500;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }
}
