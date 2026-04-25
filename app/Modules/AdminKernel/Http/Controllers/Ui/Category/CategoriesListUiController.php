<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Category;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CategoriesListUiController
{
    public function __construct(
        private Twig                                $twig,
        private UiPermissionService                 $uiPermissionService,
        private LanguageRepositoryInterface          $languageRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'        => $this->uiPermissionService->hasPermission($adminId, 'categories.create.api'),
            'can_update'        => $this->uiPermissionService->hasPermission($adminId, 'categories.update.api'),
            'can_update_sort'   => $this->uiPermissionService->hasPermission($adminId, 'categories.update_sort.api'),
            'can_active'        => $this->uiPermissionService->hasPermission($adminId, 'categories.set_active.api'),
            'can_upsert_images' => $this->uiPermissionService->hasPermission($adminId, 'categories.images.upsert.api'),
            'can_view_detail'   => $this->uiPermissionService->hasPermission($adminId, 'categories.detail.ui'),
        ];

        $languages     = $this->languageRepository->listAll();
        $languagesData = [];

        foreach ($languages->items as $language) {
            $languagesData[] = [
                'id'   => $language->id,
                'code' => $language->code,
                'name' => $language->name,
            ];
        }

        return $this->twig->render($response, 'pages/categories/categories_list.twig', [
            'capabilities' => $capabilities,
            'languages'    => $languagesData,
        ]);
    }
}


