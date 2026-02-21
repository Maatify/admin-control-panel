<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 03:14
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages;

use Maatify\AdminKernel\Domain\I18n\Language\Validation\LanguageSetActiveSchema;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;

final readonly class LanguagesSetActiveController
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
        $this->validationGuard->check(new LanguageSetActiveSchema(), $body);

        // 2) Type narrowing (phpstan-safe)
        $languageId = $body['language_id'];
        $isActive = $body['is_active'];

        if (! is_int($languageId) || ! is_bool($isActive)) {
            // Defensive – should never happen after validation
            throw new RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $this->languageService->setLanguageActive(
            languageId: $languageId,
            isActive  : $isActive
        );

        // 4) No Content
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
