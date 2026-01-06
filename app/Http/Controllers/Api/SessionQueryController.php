<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\DTO\Session\SessionListQueryDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SessionQueryController
{
    public function __construct(
        private SessionListReaderInterface $reader
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $page = isset($body['page']) ? (int)$body['page'] : 1;
        $perPage = isset($body['per_page']) ? (int)$body['per_page'] : 20;
        $filters = isset($body['filters']) && is_array($body['filters']) ? $body['filters'] : [];

        $query = new SessionListQueryDTO(
            page: $page,
            per_page: $perPage,
            filters: $filters
        );

        $result = $this->reader->getSessions($query);

        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
