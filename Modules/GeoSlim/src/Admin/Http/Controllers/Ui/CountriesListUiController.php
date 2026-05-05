<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CountriesListUiController
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
            'can_create'                    => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.create.api'),
            'can_update'                    => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.update.api'),
            'can_active'                    => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.set_active.api'),
            'can_update_sort'               => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.update_sort.api'),
            'can_view_country_translations' => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.translations.list.ui'),
            'can_view_cities'               => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.list.ui'),
        ];

        return $this->twig->render(
            $response,
            'pages/geo/countries_list.twig',
            ['capabilities' => $capabilities]
        );
    }
}

