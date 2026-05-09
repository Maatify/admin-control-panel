<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Service\GeoQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CitiesListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
        private GeoQueryService $geoQueryService,
    ) {
    }

    /**
     * @param array{country_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $countryId = (int) $args['country_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        try {
            $country = $this->geoQueryService->getCountryById($countryId);
        } catch (CountryNotFoundException) {
            throw new EntityNotFoundException('Country', $countryId);
        }

        $capabilities = [
            'can_create'                 => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.create.api'),
            'can_update'                 => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.update.api'),
            'can_active'                 => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.set_active.api'),
            'can_update_sort'            => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.update_sort.api'),
            'can_view_city_translations' => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.translations.list.ui'),
            'can_view_countries'         => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.list.ui'),
        ];

        return $this->twig->render(
            $response,
            'pages/geo/cities_list.twig',
            [
                'country'      => $country->jsonSerialize(),
                'capabilities' => $capabilities,
            ]
        );
    }
}

