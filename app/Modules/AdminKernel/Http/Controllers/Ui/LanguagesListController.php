<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 03:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class LanguagesListController
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
            'can_create' => $this->authorizationService->hasPermission($adminId, 'languages.create.api'),
            'can_update' => $this->authorizationService->hasPermission($adminId, 'languages.update.settings.api'),
            'can_update_name'    => $this->authorizationService->hasPermission($adminId, 'languages.update.name.api'),
            'can_update_sort' => $this->authorizationService->hasPermission($adminId, 'languages.update.sort.api'),
            'can_active' => $this->authorizationService->hasPermission($adminId, 'languages.set.active.api'),
            'can_fallback_set' => $this->authorizationService->hasPermission($adminId, 'languages.set.fallback.api'),
            'can_fallback_clear' => $this->authorizationService->hasPermission($adminId, 'languages.clear.fallback.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/i18n/languages_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
