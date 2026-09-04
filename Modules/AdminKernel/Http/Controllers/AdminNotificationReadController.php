<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationReadMarkerInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\History\MarkNotificationReadDTO;
use Maatify\AdminKernel\Validation\Schemas\Admin\AdminNotificationReadSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminNotificationReadController
{
    public function __construct(
        private readonly AdminNotificationReadMarkerInterface $marker,
        private ValidationGuard $validationGuard,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
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
