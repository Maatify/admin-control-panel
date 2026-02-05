<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 13:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n;

use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationValueUpsertSchema;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class TranslationValueUpsertController
{
    public function __construct(
        private TranslationWriteService $translationWriteService,
        private ValidationGuard $validationGuard
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate payload
        $this->validationGuard->check(new TranslationValueUpsertSchema(), $body);

        // 2) Explicit type narrowing (phpstan-safe)
        $languageId = $body['language_id'] ?? null;
        $keyId      = $body['key_id'] ?? null;
        $value      = $body['value'] ?? null;

        if (!\is_int($languageId) || !\is_int($keyId) || !\is_string($value)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Call domain service (no logic here)
        $this->translationWriteService->upsertTranslation(
            languageId: $languageId,
            keyId: $keyId,
            value: $value
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

