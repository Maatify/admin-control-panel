<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 23:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDropdownReaderInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopesDropdownController
{
    public function __construct(
        private I18nScopeDropdownReaderInterface $reader,
        private JsonResponseFactory $json,

    ) {}

    public function __invoke(
        Request $request,
        Response $response
    ): Response {

        $items = $this->reader->getDropdownList();

        return $this->json->data($response, $items);

    }
}
