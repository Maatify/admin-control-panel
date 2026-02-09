<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 22:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\Service\I18nScopeDomainsService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedStringRequiredSchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class I18nScopeDomainUnassignController
{
    public function __construct(
        private I18nScopeDomainsService $service,
        private ValidationGuard $validationGuard
    ) {
    }

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $scopeId = (int) $args['scope_id'];

        /** @var array{domain_code?: string} $body */
        $body = (array) $request->getParsedBody();

        // Validate body
        $this->validationGuard->check(
            new SharedStringRequiredSchema(field: 'domain_code', maxLength: 64),
            $body
        );

        $this->service->unassign(
            $scopeId,
            trim($body['domain_code'])
        );

        $response->getBody()->write(json_encode([
            'status' => 'ok',
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
