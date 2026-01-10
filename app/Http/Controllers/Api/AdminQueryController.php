<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 00:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Admin\Reader\AdminQueryReaderInterface;
use App\Domain\List\AdminListCapabilities;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ListFilterResolver;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AdminQueryController
{
    public function __construct(
        private AdminQueryReaderInterface $reader,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        // 2) Build canonical ListQueryDTO
        /** @var array{
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

        $query = ListQueryDTO::fromArray($validated);

        // 3) Admin LIST capabilities
        $capabilities = AdminListCapabilities::define();

        // 4) Resolve filters (INSTANCE call)
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Execute reader
        $result = $this->reader->queryAdmins($query, $filters);

        // 6) Return JSON
        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

