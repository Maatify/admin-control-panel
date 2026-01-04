<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\NotificationReadRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationQueryController
{
    public function __construct(
        private NotificationReadRepositoryInterface $repository
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $notifications = [];

        // Priority filters
        if (isset($params['status']) && is_string($params['status']) && $params['status'] !== '') {
            $notifications = $this->repository->findByStatus($params['status']);
        } elseif (isset($params['channel']) && is_string($params['channel']) && $params['channel'] !== '') {
            $notifications = $this->repository->findByChannel($params['channel']);
        } elseif (
            isset($params['from']) && is_string($params['from']) && $params['from'] !== '' &&
            isset($params['to']) && is_string($params['to']) && $params['to'] !== ''
        ) {
            try {
                $from = new DateTimeImmutable($params['from']);
                $to = new DateTimeImmutable($params['to']);
                $notifications = $this->repository->findByDateRange($from, $to);
            } catch (\Exception) {
                // Invalid date format, return empty list
                $notifications = [];
            }
        } elseif (isset($params['admin_id']) && is_string($params['admin_id']) && ctype_digit($params['admin_id'])) {
            $notifications = $this->repository->findByAdminId((int)$params['admin_id']);
        } else {
            // Default: show failed notifications
            $notifications = $this->repository->findByStatus('failed');
        }

        $payload = json_encode($notifications);
        if ($payload === false) {
            $payload = '[]';
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
