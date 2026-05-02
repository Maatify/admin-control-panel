<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class ProvidersListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.providers.create.api'),
            'can_update' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.providers.update.api'),
            'can_active' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.providers.set_active.api'),
            'can_update_sort' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.providers.update_sort.api'),
            'can_delete' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.providers.delete.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/exchange_rates/providers_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
