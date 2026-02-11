<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 14:47
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Repository\I18n\ScopeDomains\PdoI18nScopeDomainsListReader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeDomainsDropdownController
{
    public function __construct(
        private PdoI18nScopeDomainsListReader $reader,
        private JsonResponseFactory $json,

    ) {}

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $scopeId = (int) ($args['scope_id']);

        $items = $this->reader->listByScopeId($scopeId);
        return $this->json->data($response, $items);

    }
}
