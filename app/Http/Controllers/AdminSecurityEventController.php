<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AdminSecurityEventReaderInterface;
use App\Domain\DTO\Audit\GetMySecurityEventsQueryDTO;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminSecurityEventController
{
    public function __construct(
        private AdminSecurityEventReaderInterface $securityEventReader
    ) {
    }

    public function getMySecurityEvents(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']) ?: '');
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $params = $request->getQueryParams();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;

        // Hard limits on pagination
        $limit = max(1, min(100, $limit));
        $page = max(1, $page);

        $startDate = isset($params['start_date']) ? DateTimeImmutable::createFromFormat('Y-m-d', $params['start_date']) : null;
        if ($startDate === false) {
            $startDate = null;
        } elseif ($startDate !== null) {
            $startDate = $startDate->setTime(0, 0, 0);
        }

        $endDate = isset($params['end_date']) ? DateTimeImmutable::createFromFormat('Y-m-d', $params['end_date']) : null;
        if ($endDate === false) {
            $endDate = null;
        } elseif ($endDate !== null) {
            $endDate = $endDate->setTime(23, 59, 59);
        }

        $query = new GetMySecurityEventsQueryDTO(
            $adminId,
            $page,
            $limit,
            $params['event_type'] ?? null,
            $startDate,
            $endDate
        );

        $events = $this->securityEventReader->getMySecurityEvents($query);

        $response->getBody()->write(json_encode(['data' => $events]) ?: '');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
