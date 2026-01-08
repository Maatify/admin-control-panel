<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\DTO\AdminList\AdminListQueryDTO;
use App\Infrastructure\Reader\Admin\PdoAdminListReader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AdminListController
{
    public function __construct(
        private PdoAdminListReader $adminListReader
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = isset($params['search']) ? (string)$params['search'] : null;

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
            search: $search
        );

        $result = $this->adminListReader->listAdmins($query);

        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
