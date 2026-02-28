<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 12:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainUpdaterInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Domain\I18n\Domain\Validation\I18nDomainUpdateSortSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeDomainSortController
{
    public function __construct(
        private I18nDomainUpdaterInterface $writer,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        $this->validationGuard->check(new I18nDomainUpdateSortSchema(), $body);

        $id = 0;
        if (isset($body['id']) && is_numeric($body['id'])) {
            $id = (int)$body['id'];
        }

        $position = 0;
        if (isset($body['position']) && is_numeric($body['position'])) {
            $position = (int)$body['position'];
        }


        if (! $this->writer->existsById($id)) {
            throw new EntityNotFoundException('I18nDomain', (string)$id);
        }

        $this->writer->repositionSortOrder($id, $position);

        return $this->json->data($response, ['status' => 'ok']);
    }
}
