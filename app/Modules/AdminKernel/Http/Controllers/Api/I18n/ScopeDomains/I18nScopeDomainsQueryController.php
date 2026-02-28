<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 17:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsQueryReaderInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\List\I18nScopeDomainsListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class I18nScopeDomainsQueryController
{
    public function __construct(
        private I18nScopeDomainsQueryReaderInterface $reader,
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    ) {
    }

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $scopeId = (int) $args['scope_id'];

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
        $capabilities = I18nScopeDomainsListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Resolve scope_code from scope_id (Reader only)
        $scope = $this->scopeDetailsReader->getScopeDetailsById($scopeId);
        $scopeCode = $scope->code;

        // 6) Execute reader
        $result = $this->reader->queryScopeDomains($scopeCode, $query, $filters);

        // 7) Return JSON
        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
