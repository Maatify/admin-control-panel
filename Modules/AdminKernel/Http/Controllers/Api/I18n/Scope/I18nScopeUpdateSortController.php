<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Scope\Validation\I18nScopeUpdateSortSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeUpdaterInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeUpdateSortController
{
    public function __construct(
        private I18nScopeUpdaterInterface $writer,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        $this->validationGuard->check(new I18nScopeUpdateSortSchema(), $body);

        $id = 0;
        if (isset($body['id']) && is_numeric($body['id'])) {
            $id = (int)$body['id'];
        }

        $position = 0;
        if (isset($body['position']) && is_numeric($body['position'])) {
            $position = (int)$body['position'];
        }


        if (! $this->writer->existsById($id)) {
            throw new EntityNotFoundException('I18nScope', (string)$id);
        }

        $this->writer->repositionSortOrder($id, $position);

        return $this->json->data($response, ['status' => 'ok']);
    }
}

