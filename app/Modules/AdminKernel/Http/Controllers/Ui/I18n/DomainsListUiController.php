<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 12:57
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class DomainsListUiController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorizationService,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'        => $this->authorizationService->hasPermission($adminId, 'i18n.domains.create'),
            'can_update'        => $this->authorizationService->hasPermission($adminId, 'i18n.domains.update'),
            'can_change_code'   => $this->authorizationService->hasPermission($adminId, 'i18n.domains.change_code'),
            'can_set_active'    => $this->authorizationService->hasPermission($adminId, 'i18n.domains.set_active'),
            'can_update_sort'   => $this->authorizationService->hasPermission($adminId, 'i18n.domains.update_sort'),
            'can_update_meta'   => $this->authorizationService->hasPermission($adminId, 'i18n.domains.update_metadata'),
        ];

        return $this->twig->render(
            $response,
            'pages/i18n/domains.list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
