<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Category;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Category\Service\CategoryQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CategoryImagesListUiController
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
            'can_view_categories' => $this->uiPermissionService->hasPermission($adminId, 'categories.list.ui'),
            'can_view_detail'     => $this->uiPermissionService->hasPermission($adminId, 'categories.detail.ui'),
            'can_upsert_images'   => $this->uiPermissionService->hasPermission($adminId, 'categories.images.upsert.api'),
            'can_delete_images'   => $this->uiPermissionService->hasPermission($adminId, 'categories.images.delete.api'),
        ];

        return $this->view->render($response, 'pages/categories/category_images.twig', [
            'category'     => $category->jsonSerialize(),
            'capabilities' => $capabilities,
        ]);
    }
}
