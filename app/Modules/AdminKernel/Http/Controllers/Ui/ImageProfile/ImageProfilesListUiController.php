<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ImageProfile;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class ImageProfilesListUiController
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
            'can_create' => $this->uiPermissionService->hasPermission($adminId, 'image_profiles.create.api'),
            'can_update' => $this->uiPermissionService->hasPermission($adminId, 'image_profiles.update.api'),
            'can_active' => $this->uiPermissionService->hasPermission($adminId, 'image_profiles.set_active.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/image_profiles/image_profiles_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
