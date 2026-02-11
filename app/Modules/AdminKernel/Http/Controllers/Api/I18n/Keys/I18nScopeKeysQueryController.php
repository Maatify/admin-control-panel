<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 02:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys;

use Maatify\AdminKernel\Domain\I18n\Keys\DTO\TranslationKeyListCapabilities;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeysQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeKeysQueryController
{
    public function __construct(
        private ValidationGuard $validationGuard,
        private I18nScopeKeysQueryReaderInterface $reader,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json
    )
    {
    }

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        $scopeId = (int)$args['scope_id'];

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
        $capabilities = TranslationKeyListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Execute reader
        $result = $this->reader->queryScopeKeys(
            $scopeId,
            $query,
            $filters
        );

        // 6) Return JSON
        return $this->json->data($response, $result, 200);
    }
}
