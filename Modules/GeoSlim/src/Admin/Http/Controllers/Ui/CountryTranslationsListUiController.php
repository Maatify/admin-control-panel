<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Service\GeoQueryService;
use Maatify\LanguageCore\Contract\LanguageContextQueryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CountryTranslationsListUiController
{
    public function __construct(
        private Twig $view,
        private UiPermissionService $uiPermissionService,
        private GeoQueryService $geoQueryService,
        private LanguageContextQueryInterface $languageContextQuery,
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
            'can_upsert'       => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.translations.upsert.api'),
            'can_delete'       => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.translations.delete.api'),
            'can_view_countries' => $this->uiPermissionService->hasPermission($adminId, 'geo.countries.list.ui'),
        ];

        return $this->view->render($response, 'pages/geo/country_translations.twig', [
            'country'      => $country->jsonSerialize(),
            'languages'    => $this->languageContextQuery->listAllWithContext()->items,
            'capabilities' => $capabilities,
        ]);
    }
}

