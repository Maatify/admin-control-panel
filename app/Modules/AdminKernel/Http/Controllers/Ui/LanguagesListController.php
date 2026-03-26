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

use Maatify\AdminKernel\Application\Security\UiPermissionService;

use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class LanguagesListController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->uiPermissionService->hasPermission($adminId, 'languages.create.api'),
            'can_update' => $this->uiPermissionService->hasPermission($adminId, 'languages.update.settings.api'),
            'can_update_name'    => $this->uiPermissionService->hasPermission($adminId, 'languages.update.name.api'),
            'can_update_code'    => $this->uiPermissionService->hasPermission($adminId, 'languages.update.code.api'),
            'can_update_sort' => $this->uiPermissionService->hasPermission($adminId, 'languages.update.sort.api'),
            'can_active' => $this->uiPermissionService->hasPermission($adminId, 'languages.set.active.api'),
            'can_fallback_set' => $this->uiPermissionService->hasPermission($adminId, 'languages.set.fallback.api'),
            'can_fallback_clear' => $this->uiPermissionService->hasPermission($adminId, 'languages.clear.fallback.api'),
            'can_view_language_translations' => $this->uiPermissionService->hasPermission($adminId, 'languages.translations.list'),
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
