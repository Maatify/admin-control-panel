<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminListReaderInterface;
use App\Domain\DTO\AdminList\AdminListQueryDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

readonly class AdminListController
{
    public function __construct(
        private AdminListReaderInterface $adminListReader
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();

            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;

            $adminId = isset($params['id']) && $params['id'] !== '' ? (int)$params['id'] : null;
            $email = isset($params['email']) && $params['email'] !== '' ? (string)$params['email'] : null;

            // Validation limits
            if ($page < 1) {
                $page = 1;
            }
            if ($perPage < 1) {
                $perPage = 10;
            }
            if ($perPage > 100) {
                $perPage = 100;
            }

            $query = new AdminListQueryDTO(
                page: $page,
                perPage: $perPage,
                adminId: $adminId,
                email: $email
            );

            $result = $this->adminListReader->listAdmins($query);

            $json = json_encode($result, JSON_THROW_ON_ERROR);
            $response->getBody()->write($json);

            return $response->withHeader('Content-Type', 'application/json');

        } catch (Throwable $e) {
            $errorPayload = json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
            $response->getBody()->write($errorPayload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
