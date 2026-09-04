<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 14:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n;

use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\List\LanguageTranslationValueListCapabilities;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\LanguageTranslationValueQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class LanguageTranslationsQueryController
{
    public function __construct(
        private LanguageTranslationValueQueryReaderInterface $reader,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json,
    ) {
    }

    /**
     * @param array{language_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $languageId = (int) $args['language_id'];

        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape (language_id + list query payload)
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

        // 3) Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($validated);

        // 4) Capabilities
        $capabilities = LanguageTranslationValueListCapabilities::define();

        // 5) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 6) Execute reader
        $result = $this->reader->queryTranslationValues($languageId, $query, $filters);

        // 7) Return JSON
        return $this->json->data($response, $result);

    }
}

