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
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Language\LanguageLookupInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

// UI capabilities only – no business logic here
final readonly class LanguageTranslationsListUiController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorizationService,
        private LanguageLookupInterface $repository
    )
    {
    }

    /**
     * @param array{language_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $languageId = (int) $args['language_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $language = $this->repository->getById($languageId);

        if ($language === null) {
            throw new EntityNotFoundException('Language', $languageId);
        }

        $viewModel = [
            'id' => $language->id,
            'name' => $language->name,
            'code' => $language->code,
            'is_active' => $language->isActive,
            'direction' => $language->direction->value,
            'icon' => $language->icon,
            'fallback_language_id' => $language->fallbackLanguageId,
        ];

        $capabilities = [
            'can_upsert'        => $this->authorizationService->hasPermission($adminId, 'languages.translations.upsert'),
            'can_delete'        => $this->authorizationService->hasPermission($adminId, 'languages.translations.delete'),
            'can_view_languages'        => $this->authorizationService->hasPermission($adminId, 'languages.list'),
        ];
        return $this->twig->render(
            $response,
            'pages/i18n/language_translations.list.twig',
            [
                'capabilities' => $capabilities,
                'language' =>$viewModel
            ]
        );
    }
}
