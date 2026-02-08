<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 03:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages;

use Maatify\AdminKernel\Validation\Schemas\I18n\Language\LanguageSetFallbackSchema;
use Maatify\I18n\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final readonly class LanguagesSetFallbackController
{
    public function __construct(
        private LanguageManagementService $languageService,
        private ValidationGuard $validationGuard
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new LanguageSetFallbackSchema(), $body);

        // 2) Type narrowing (phpstan-safe)
        $languageId = $body['language_id'];
        $fallbackLanguageId = $body['fallback_language_id'];

        if (! is_int($languageId) || ! is_int($fallbackLanguageId)) {
            // Defensive – should never happen after validation
            throw new RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $this->languageService->setFallbackLanguage(
            languageId        : $languageId,
            fallbackLanguageId: $fallbackLanguageId
        );

        // 4) No Content
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
