<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\I18n\Scope\Validation\I18nScopeUpdateMetadataSchema;
use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeUpdaterInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeUpdateMetadataController
{
    public function __construct(
        private I18nScopeUpdaterInterface $writer,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new I18nScopeUpdateMetadataSchema(),
            $body
        );

        $id = 0;
        if (isset($body['id']) && is_numeric($body['id'])) {
            $id = (int) $body['id'];
        }

        $name = null;
        if (isset($body['name']) && is_string($body['name'])) {
            $name = $body['name'];
        }

        if (! $this->writer->existsById($id)) {
            throw new EntityNotFoundException('I18nScope', (string) $id);
        }

        $description = null;
        if (isset($body['description']) && is_string($body['description'])) {
            $description = $body['description'];
        }

        // must update at least one field
        if ($name === null && $description === null) {
            throw new InvalidOperationException(
                'I18nScope',
                'update-metadata',
                'At least one field (name or description) must be provided'
            );
        }

        $this->writer->updateMetadata($id, $name, $description);

        $response->getBody()->write(json_encode([
            'status' => 'ok',
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
