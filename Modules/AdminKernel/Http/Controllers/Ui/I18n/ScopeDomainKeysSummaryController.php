<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Application\Security\UiPermissionService;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainDetailsReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;
use Maatify\I18n\Exception\DomainScopeViolationException;
use Maatify\LanguageCore\Contract\LanguageContextQueryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class ScopeDomainKeysSummaryController
{
    public function __construct(
        private Twig $view,
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private I18nDomainDetailsReaderInterface $domainDetailsReader,
        private I18nScopeDomainsInterface $scopeDomainsReader,
        private UiPermissionService $uiPermissionService,
        private LanguageContextQueryInterface $languageContextQuery,
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

        $capabilities = [
            'can_view_i18n_scopes'  => $this->uiPermissionService->hasPermission($adminId, 'i18n.scopes.list'),
            'can_view_i18n_scopes_details'  => $this->uiPermissionService->hasPermission($adminId, 'i18n.scopes.details'),
            'can_view_i18n_scopes_domains_translations'  => $this->uiPermissionService->hasPermission($adminId, 'i18n.scopes.domains.translations'),

        ];

        return $this->view->render($response, 'pages/i18n/scope_domain_keys_summary.twig', [
            'scope'        => $scope->jsonSerialize(),
            'domain'       => $domain->jsonSerialize(),
            'languages'    => $this->languageContextQuery->listAllWithContext()->items,
            'capabilities' => $capabilities,
        ]);
    }
}
