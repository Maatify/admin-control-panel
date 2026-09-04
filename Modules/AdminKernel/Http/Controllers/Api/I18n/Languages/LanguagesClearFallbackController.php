<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 05:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages;

use Maatify\AdminKernel\Domain\I18n\Language\Validation\LanguageClearFallbackSchema;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class LanguagesClearFallbackController
{
    public function __construct(
        private LanguageManagementService $languageService,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new LanguageClearFallbackSchema(), $body);

        $languageId = $body['language_id'];

        if (! is_int($languageId)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $this->languageService->clearFallbackLanguage($languageId);

        return $response->withStatus(200);
    }
}
