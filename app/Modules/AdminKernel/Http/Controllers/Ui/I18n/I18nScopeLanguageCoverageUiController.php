<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class I18nScopeLanguageCoverageUiController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorizationService,
        private I18nScopeDetailsRepositoryInterface $scopeReader,
        private LanguageRepositoryInterface $languageRepository
    ) {
    }

    /**
     * @param array{scope_id: string, language_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $scopeId = (int)$args['scope_id'];
        $languageId = (int)$args['language_id'];

        $scope = $this->scopeReader->getScopeDetailsById($scopeId);
        $language = $this->languageRepository->getById($languageId);

        // Reuse existing capabilities or define new granular ones?
        // Existing pages use 'can_view_scopes' etc.
        // We'll stick to scope read permissions + maybe coverage specific?
        // For now, reuse basic scope read.

        $capabilities = [
            'can_view_scopes' => $this->authorizationService->hasPermission($adminId, 'i18n.scopes.list'),
            'can_view_scope_details' => $this->authorizationService->hasPermission($adminId, 'i18n.scopes.details'),
            // We assume if they can view details, they can view coverage
        ];

        return $this->twig->render(
            $response,
            'pages/i18n/scope_language_coverage.twig',
            [
                'capabilities' => $capabilities,
                'scope' => $scope,
                'language' => $language,
            ]
        );
    }
}
