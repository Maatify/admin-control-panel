<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-07 13:05
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\DTO\I18nScopes\I18nScopeCreateDTO;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeCreateWriterInterface;
use Maatify\AdminKernel\Validation\Schemas\I18n\I18nScopeCreateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeCreateController
{
    public function __construct(
        private I18nScopeCreateWriterInterface $writer,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new I18nScopeCreateSchema(), $body);

        $code = is_string($body['code'] ?? null) ? $body['code'] : '';
        $name = is_string($body['name'] ?? null) ? $body['name'] : '';

        $description = is_string($body['description'] ?? null)
            ? $body['description']
            : '';

        $isActive = isset($body['is_active']) && is_numeric($body['is_active'])
            ? (int)$body['is_active']
            : 1;

        $sortOrder = isset($body['sort_order']) && is_numeric($body['sort_order'])
            ? (int)$body['sort_order']
            : 0;

        if($this->writer->existsByCode($code)){
            throw new EntityAlreadyExistsException(
                'I18nScope',
                'code',
                $code
            );
        }

        $dto = new I18nScopeCreateDTO(
            code: $code,
            name: $name,
            description: $description,
            is_active: $isActive,
            sort_order: $sortOrder,
        );

        $id = $this->writer->create($dto);

        $response->getBody()->write(json_encode([
            'id' => $id,
        ], JSON_THROW_ON_ERROR));

        // NOTE:
        // This endpoint intentionally returns HTTP 200 (not 201)
        // to stay consistent with the project's unified API response policy.
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}


