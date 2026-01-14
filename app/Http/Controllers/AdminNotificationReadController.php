<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\AdminContext;
use App\Domain\Contracts\AdminNotificationReadMarkerInterface;
use App\Domain\DTO\Notification\History\MarkNotificationReadDTO;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AdminNotificationReadSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminNotificationReadController
{
    public function __construct(
        private readonly AdminNotificationReadMarkerInterface $marker,
        private ValidationGuard $validationGuard
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new \RuntimeException('AdminContext not found in request attributes');
        }
        $adminId = $adminContext->adminId;

        $this->validationGuard->check(new AdminNotificationReadSchema(), $args);

        $notificationId = (int)$args['id'];

        $dto = new MarkNotificationReadDTO(
            adminId: $adminId,
            notificationId: $notificationId
        );

        $this->marker->markAsRead($dto);

        return $response->withStatus(204);
    }
}
