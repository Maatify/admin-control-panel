<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ExchangeRates;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class RatesHistoryListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_view_history' => $this->uiPermissionService->hasPermission($adminId, 'exchange_rates.rates.history.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/exchange_rates/rates_history_list.twig',
            [
                'capabilities' => $capabilities,
                'rate_id' => $args['rate_id'] ?? '',
            ]
        );
    }
}
