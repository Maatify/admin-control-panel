<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Category\Service\CategoryQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CategoryDetailUiController
{
    public function __construct(
        private Twig                 $view,
        private UiPermissionService  $uiPermissionService,
        private CategoryQueryService $categoryQueryService,
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $categoryId = (int) $args['category_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        try {
            $category = $this->categoryQueryService->getById($categoryId);
        } catch (\Throwable) {
            throw new EntityNotFoundException('Category', $categoryId);
        }

        $capabilities = [
            'can_view_categories'     => $this->uiPermissionService->hasPermission($adminId, 'categories.list.ui'),
            'can_update'              => $this->uiPermissionService->hasPermission($adminId, 'categories.update.api'),
            'can_active'              => $this->uiPermissionService->hasPermission($adminId, 'categories.set_active.api'),
            'can_update_sort'         => $this->uiPermissionService->hasPermission($adminId, 'categories.update_sort.api'),
            'can_view_sub_categories' => $this->uiPermissionService->hasPermission($adminId, 'categories.sub_categories.list.ui'),
            'can_view_images'         => $this->uiPermissionService->hasPermission($adminId, 'categories.images.list.ui'),
            'can_view_translations'   => $this->uiPermissionService->hasPermission($adminId, 'categories.translations.list.ui'),
            'can_view_settings'       => $this->uiPermissionService->hasPermission($adminId, 'categories.settings.list.ui'),
        ];

        return $this->view->render($response, 'pages/categories/category_detail.twig', [
            'category'     => $category->jsonSerialize(),
            'capabilities' => $capabilities,
        ]);
    }
}

