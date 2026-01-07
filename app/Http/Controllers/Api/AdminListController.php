<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminListReaderInterface;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminListController
{
    public function __construct(
        private AdminListReaderInterface $reader,
        private AuthorizationService $authorizationService
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        assert(is_int($adminId));

        $this->authorizationService->checkPermission($adminId, 'sessions.view_all');

        $admins = $this->reader->getAdmins();

        $response->getBody()->write(json_encode(['data' => $admins], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
