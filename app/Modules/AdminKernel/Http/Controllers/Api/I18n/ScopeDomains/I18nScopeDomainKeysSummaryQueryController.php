<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainDetailsReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsInterface;
use Maatify\AdminKernel\Domain\I18n\Translations\I18nScopeDomainKeysSummaryQueryReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Translations\List\I18nScopeDomainKeysSummaryListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\I18n\Exception\DomainScopeViolationException;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeDomainKeysSummaryQueryController
{
    public function __construct(
        private I18nScopeDomainKeysSummaryQueryReaderInterface $translationsQueryReader,
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private I18nDomainDetailsReaderInterface $domainDetailsReader,
        private I18nScopeDomainsInterface $scopeDomainsReader,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json,
    ) {}

    /**
     * @param array{scope_id: string, domain_id: string} $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $scopeId = (int) $args['scope_id'];
        $domainId = (int) $args['domain_id'];

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /**
         * @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{
         *     global?: string,
         *     columns?: array<string, string>
         *   },
         *   date?: array{
         *     from?: string,
         *     to?: string
         *   }
         * } $validated
         */
        $validated = $body;

        // 2) Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($validated);

        // 3) Capabilities
        $capabilities = I18nScopeDomainKeysSummaryListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Resolve scope_code from scope_id (Reader only)
        $scope = $this->scopeDetailsReader->getScopeDetailsById($scopeId);
        $scopeCode = $scope->code;

        $domain = $this->domainDetailsReader->getDomainDetailsById($domainId);
        $domainCode = $domain->code;

        if(!$this->scopeDomainsReader->isAssigned($scopeCode, $domainCode)){
            throw new DomainScopeViolationException($scopeCode, $domainCode);
        }

        $result = $this->translationsQueryReader->query($scopeCode, $domainCode, $query, $filters);

        return $this->json->data($response, $result);
    }
}
