<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Service\GeoQueryService;
use Maatify\LanguageCore\Contract\LanguageContextQueryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CityTranslationsListUiController
{
    public function __construct(
        private Twig $view,
        private UiPermissionService $uiPermissionService,
        private GeoQueryService $geoQueryService,
        private LanguageContextQueryInterface $languageContextQuery,
    ) {
    }

    /**
     * @param array{country_id: string, city_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $cityId = (int) $args['city_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        try {
            $city = $this->geoQueryService->getCityById($cityId);
        } catch (CityNotFoundException) {
            throw new EntityNotFoundException('City', $cityId);
        }

        $capabilities = [
            'can_upsert'    => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.translations.upsert.api'),
            'can_delete'    => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.translations.delete.api'),
            'can_view_cities' => $this->uiPermissionService->hasPermission($adminId, 'geo.cities.list.ui'),
        ];

        return $this->view->render($response, 'pages/geo/city_translations.twig', [
            'city'         => $city->jsonSerialize(),
            'languages'    => $this->languageContextQuery->listAllWithContext()->items,
            'capabilities' => $capabilities,
        ]);
    }
}

