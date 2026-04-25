<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Category;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class CategoryDetailUiController
{
    public function __construct(
        private Twig                              $view,
        private UiPermissionService               $uiPermissionService,
        private CategoryQueryService              $categoryQueryService,
        private LanguageRepositoryInterface        $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository,
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

        $languages     = $this->languageRepository->listAll();
        $languagesData = [];

        foreach ($languages->items as $language) {
            $settings = $this->settingsRepository->getByLanguageId($language->id);
            $languagesData[] = [
                'id'        => $language->id,
                'code'      => $language->code,
                'name'      => $language->name,
                'direction' => $settings?->direction->value ?? 'ltr',
                'icon'      => $settings?->icon ?? '',
            ];
        }

        $capabilities = [
            'can_view_categories'     => $this->uiPermissionService->hasPermission($adminId, 'categories.list.ui'),
            'can_view_sub_categories' => $this->uiPermissionService->hasPermission($adminId, 'categories.sub_categories.list.api'),
            'can_view_settings'       => $this->uiPermissionService->hasPermission($adminId, 'categories.settings.list.api'),
            'can_upsert_settings'     => $this->uiPermissionService->hasPermission($adminId, 'categories.settings.upsert.api'),
            'can_delete_settings'     => $this->uiPermissionService->hasPermission($adminId, 'categories.settings.delete.api'),
            'can_view_images'         => $this->uiPermissionService->hasPermission($adminId, 'categories.images.list.api'),
            'can_upsert_images'       => $this->uiPermissionService->hasPermission($adminId, 'categories.images.upsert.api'),
            'can_delete_images'       => $this->uiPermissionService->hasPermission($adminId, 'categories.images.delete.api'),
            'can_view_translations'   => $this->uiPermissionService->hasPermission($adminId, 'categories.translations.list.ui'),
            'can_upsert'              => $this->uiPermissionService->hasPermission($adminId, 'categories.translations.upsert.api'),
            'can_delete'              => $this->uiPermissionService->hasPermission($adminId, 'categories.translations.delete.api'),
            'can_update'              => $this->uiPermissionService->hasPermission($adminId, 'categories.update.api'),
            'can_active'              => $this->uiPermissionService->hasPermission($adminId, 'categories.set_active.api'),
            'can_update_sort'         => $this->uiPermissionService->hasPermission($adminId, 'categories.update_sort.api'),
        ];

        return $this->view->render($response, 'pages/categories/category_detail.twig', [
            'category'     => $category->jsonSerialize(),
            'languages'    => $languagesData,
            'capabilities' => $capabilities,
        ]);
    }
}


