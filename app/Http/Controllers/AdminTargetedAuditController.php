<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AdminTargetedAuditReaderInterface;
use App\Domain\DTO\Audit\GetActionsTargetingMeQueryDTO;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminTargetedAuditController
{
    public function __construct(
        private AdminTargetedAuditReaderInterface $targetedAuditReader
    ) {
    }

    public function getActionsTargetingMe(Request $request, Response $response): Response
    {
        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        if (!$adminContext instanceof \App\Context\AdminContext) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']) ?: '');
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $adminId = $adminContext->adminId;

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

        $query = new GetActionsTargetingMeQueryDTO(
            $adminId,
            $page,
            $limit,
            isset($params['actor_admin_id']) ? (int)$params['actor_admin_id'] : null,
            $params['action'] ?? null,
            $startDate,
            $endDate
        );

        $actions = $this->targetedAuditReader->getActionsTargetingMe($query);

        $response->getBody()->write(json_encode(['data' => $actions]) ?: '');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
