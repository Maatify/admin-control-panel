<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Currency;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Currency\Service\CurrencyQueryService;
use Maatify\LanguageCore\Contract\LanguageContextQueryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CurrencyTranslationsListUiController
{
    public function __construct(
        private Twig $view,
        private UiPermissionService $uiPermissionService,
        private CurrencyQueryService $currencyQueryService,
        private LanguageContextQueryInterface $languageContextQuery,

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

        $capabilities = [
            'can_upsert' => $this->uiPermissionService->hasPermission($adminId, 'currencies.translations.upsert.api'),
            'can_delete' => $this->uiPermissionService->hasPermission($adminId, 'currencies.translations.delete.api'),
            'can_view_currencies' => $this->uiPermissionService->hasPermission($adminId, 'currencies.list.ui'),
        ];

        return $this->view->render($response, 'pages/currencies/currency_translations.twig', [
            'currency'     => $currency->jsonSerialize(),
            'languages'    => $this->languageContextQuery->listAllWithContext()->items,
            'capabilities' => $capabilities,
        ]);
    }
}
