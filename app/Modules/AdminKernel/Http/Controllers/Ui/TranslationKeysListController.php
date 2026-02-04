<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class TranslationKeysListController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorizationService,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'             => $this->authorizationService->hasPermission($adminId, 'i18n.keys.create.api'),
            'can_update_name'        => $this->authorizationService->hasPermission($adminId, 'i18n.keys.update.name.api'),
            'can_update_description' => $this->authorizationService->hasPermission($adminId, 'i18n.keys.update.description.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/i18n/keys_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
