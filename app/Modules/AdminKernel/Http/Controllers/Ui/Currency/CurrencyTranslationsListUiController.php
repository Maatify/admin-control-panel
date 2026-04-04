<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Currency;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Currency\Service\CurrencyQueryService;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CurrencyTranslationsListUiController
{
    public function __construct(
        private Twig $view,
        private UiPermissionService $uiPermissionService,
        private CurrencyQueryService $currencyQueryService,
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    ) {
    }

    /**
     * @param array{currency_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $currencyId = (int) $args['currency_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        try {
            $currency = $this->currencyQueryService->getById($currencyId);
        } catch (\Maatify\Currency\Exception\CurrencyNotFoundException) {
            throw new EntityNotFoundException('Currency', $currencyId);
        }

        $languages = $this->languageRepository->listAll();
        $languagesData = [];

        foreach ($languages->items as $language) {
            $settings = $this->settingsRepository->getByLanguageId($language->id);

            // Safety rule:
            // Language without settings MUST NOT appear in UI select
            if ($settings === null) {
                continue;
            }

            $languagesData[] = [
                'id'         => $language->id,
                'code'       => $language->code,
                'name'       => $language->name,
                'direction'  => $settings->direction->value,
                'icon'       => $settings->icon,
                'is_default' => $language->fallbackLanguageId === null,
            ];
        }

        $capabilities = [
            'can_upsert' => $this->uiPermissionService->hasPermission($adminId, 'currencies.translations.upsert.api'),
            'can_delete' => $this->uiPermissionService->hasPermission($adminId, 'currencies.translations.delete.api'),
            'can_view_currencies' => $this->uiPermissionService->hasPermission($adminId, 'currencies.list.ui'),
        ];

        return $this->view->render($response, 'pages/currencies/currency_translations.twig', [
            'currency'     => $currency->jsonSerialize(),
            'languages'    => $languagesData,
            'capabilities' => $capabilities,
        ]);
    }
}
