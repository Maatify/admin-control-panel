<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 16:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

// UI capabilities only – no business logic here
final readonly class TranslationsListUiController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorizationService,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_upsert'        => $this->authorizationService->hasPermission($adminId, 'i18n.translations.upsert.api'),
            'can_delete'        => $this->authorizationService->hasPermission($adminId, 'i18n.translations.delete.api'),
        ];
        return $this->twig->render(
            $response,
            'pages/i18n/translations.list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
