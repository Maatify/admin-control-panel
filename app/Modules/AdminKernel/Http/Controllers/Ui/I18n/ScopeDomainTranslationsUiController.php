<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 18:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainDetailsReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\I18n\Exception\DomainScopeViolationException;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class ScopeDomainTranslationsUiController
{
    public function __construct(
        private Twig $view,
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private I18nDomainDetailsReaderInterface $domainDetailsReader,
        private I18nScopeDomainsInterface $scopeDomainsReader,
        private AuthorizationService $authorizationService,
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    ) {
    }

    /**
     * @param array{scope_id: string, domain_id: string} $args
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $scopeId = (int) $args['scope_id'];
        $domainId = (int) $args['domain_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $scope = $this->scopeDetailsReader->getScopeDetailsById($scopeId);
        $scopeCode = $scope->code;
        $domain = $this->domainDetailsReader->getDomainDetailsById($domainId);
        $domainCode = $domain->code;

        if(!$this->scopeDomainsReader->isAssigned($scopeCode, $domainCode)){
            throw new DomainScopeViolationException($scopeCode, $domainCode);
        }

        $languages = $this->languageRepository->listAll();

        $languagesData = [];

        foreach ($languages->items as $language) {
            $settings = $this->settingsRepository->getByLanguageId($language->id);

            // Safety rule:
            // Language without settings MUST NOT appear in UI select
            if ($settings === null) {
                continue;
            }

            $languagesData[] = [
                'id'         => $language->id,
                'code'       => $language->code,
                'name'       => $language->name,
                'direction'  => $settings->direction->value,
                'icon'       => $settings->icon,
                'is_default' => $language->fallbackLanguageId === null,
            ];
        }

        $capabilities = [
            'can_view_i18n_scopes'  => $this->authorizationService->hasPermission($adminId, 'i18n.scopes.list'),
            'can_view_i18n_scopes_details'  => $this->authorizationService->hasPermission($adminId, 'i18n.scopes.details'),
            'can_view_i18n_scopes_domains_keys'  => $this->authorizationService->hasPermission($adminId, 'i18n.scopes.domains.keys'),
            'can_upsert'        => $this->authorizationService->hasPermission($adminId, 'languages.translations.upsert'),
            'can_delete'        => $this->authorizationService->hasPermission($adminId, 'languages.translations.delete'),
        ];

        return $this->view->render($response, 'pages/i18n/scope_domain_translations.twig', [
            'scope'        => $scope->jsonSerialize(),
            'domain'       => $domain->jsonSerialize(),
            'languages'    => $languagesData,
            'capabilities' => $capabilities,
        ]);
    }
}
