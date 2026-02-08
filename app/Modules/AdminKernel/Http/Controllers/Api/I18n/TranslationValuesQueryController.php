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

use Maatify\AdminKernel\Domain\I18n\Reader\TranslationValueQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\List\TranslationValueListCapabilities;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationValue\TranslationValuesQuerySchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class TranslationValuesQueryController
{
    public function __construct(
        private TranslationValueQueryReaderInterface $reader,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape (language_id + list query payload)
        $this->validationGuard->check(new TranslationValuesQuerySchema(), $body);

        // 2) Explicit type narrowing (phpstan-safe)
        $languageId = $body['language_id'] ?? null;
        if (!is_int($languageId)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        /**
         * @var array{
         *   language_id: int,
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
        $capabilities = TranslationValueListCapabilities::define();

        // 5) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 6) Execute reader
        $result = $this->reader->queryTranslationValues($languageId, $query, $filters);

        // 7) Return JSON
        $response->getBody()->write(\json_encode($result, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}

