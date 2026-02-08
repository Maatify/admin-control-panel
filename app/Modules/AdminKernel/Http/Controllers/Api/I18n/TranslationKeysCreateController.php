<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 12:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n;

use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey\TranslationKeyCreateSchema;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class TranslationKeysCreateController
{
    public function __construct(
        private TranslationWriteService $translationWriteService,
        private ValidationGuard $validationGuard
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new TranslationKeyCreateSchema(), $body);

        // 2) Explicit type narrowing (phpstan-safe)
        $keyName = $body['key_name'];

        if (!is_string($keyName)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        $description = null;
        if (array_key_exists('description', $body)) {
            if (!is_string($body['description'])) {
                throw new \RuntimeException('Invalid description value.');
            }
            $description = $body['description'];
        }

        // TODO fix at the domain layer
        // 3) Execute service
        $this->translationWriteService->createKey(
            key: $keyName,
            description: $description
        );

        // 4) Response
        $response->getBody()->write(
            json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
