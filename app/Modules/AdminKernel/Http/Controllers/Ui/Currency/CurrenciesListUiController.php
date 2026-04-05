<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Currency;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CurrenciesListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->uiPermissionService->hasPermission($adminId, 'currencies.create.api'),
            'can_update' => $this->uiPermissionService->hasPermission($adminId, 'currencies.update.api'),
            'can_active' => $this->uiPermissionService->hasPermission($adminId, 'currencies.set_active.api'),
            'can_update_sort' => $this->uiPermissionService->hasPermission($adminId, 'currencies.update_sort.api'),
            'can_view_currency_translations' => $this->uiPermissionService->hasPermission($adminId, 'currencies.translations.list.ui'),
        ];

        return $this->twig->render(
            $response,
            'pages/currencies/currencies_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
