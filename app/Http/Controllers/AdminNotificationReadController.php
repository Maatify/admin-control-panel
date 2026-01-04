<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AdminNotificationReadMarkerInterface;
use App\Domain\DTO\Notification\History\MarkNotificationReadDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminNotificationReadController
{
    public function __construct(
        private readonly AdminNotificationReadMarkerInterface $marker
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            throw new \RuntimeException('Admin ID not found in request attributes');
        }

        $notificationId = (int)$args['id'];

        $dto = new MarkNotificationReadDTO(
            adminId: $adminId,
            notificationId: $notificationId
        );

        $this->marker->markAsRead($dto);

        return $response->withStatus(204);
    }
}
