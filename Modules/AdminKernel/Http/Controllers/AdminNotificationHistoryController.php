<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use DateTimeImmutable;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationHistoryReaderInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\History\AdminNotificationHistoryQueryDTO;
use Maatify\AdminKernel\Validation\Schemas\Admin\AdminNotificationHistorySchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminNotificationHistoryController
{
    public function __construct(
        private readonly AdminNotificationHistoryReaderInterface $reader,
        private ValidationGuard $validationGuard
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }
        $authAdminId = $adminContext->adminId;

        $routeAdminId = (int)$args['admin_id'];

        // Strict: only allow viewing own history
        if ($routeAdminId !== $authAdminId) {
            return $response->withStatus(403);
        }

        $queryParams = $request->getQueryParams();

        // Pass raw input + injected route param
        $input = array_merge($queryParams, ['admin_id' => $routeAdminId]);

        $this->validationGuard->check(new AdminNotificationHistorySchema(), $input);

        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
        $type = isset($queryParams['notification_type']) ? (string)$queryParams['notification_type'] : null;

        $isRead = null;
        if (isset($queryParams['is_read'])) {
            $isRead = filter_var($queryParams['is_read'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $fromDate = null;
        if (isset($queryParams['from_date']) && is_string($queryParams['from_date'])) {
            $d = DateTimeImmutable::createFromFormat('Y-m-d', $queryParams['from_date']);
            if ($d) {
                $fromDate = $d->setTime(0, 0, 0);
            }
        }

        $toDate = null;
        if (isset($queryParams['to_date']) && is_string($queryParams['to_date'])) {
            $d = DateTimeImmutable::createFromFormat('Y-m-d', $queryParams['to_date']);
            if ($d) {
                $toDate = $d->setTime(23, 59, 59);
            }
        }

        $query = new AdminNotificationHistoryQueryDTO(
            adminId: $routeAdminId,
            page: $page < 1 ? 1 : $page,
            limit: $limit < 1 ? 20 : $limit,
            notificationType: $type,
            isRead: $isRead,
            fromDate: $fromDate,
            toDate: $toDate
        );

        $history = $this->reader->getHistory($query);

        $payload = json_encode($history);
        if ($payload === false) {
            throw new \RuntimeException('Failed to encode notification history');
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
