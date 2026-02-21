<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 06:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages;

use Maatify\AdminKernel\Domain\I18n\Language\Validation\LanguageUpdateSortOrderSchema;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class LanguagesUpdateSortOrderController
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

        // 🔐 Schema validation (no nulls, correct types)
        $this->validationGuard->check(
            new LanguageUpdateSortOrderSchema(),
            $body
        );

        $languageId = $body['language_id'];
        $newSortOrder = $body['sort_order'];

        // Defensive guard (should never happen after validation)
        if (! is_int($languageId) || ! is_int($newSortOrder)) {
            throw new AdminKernelValidationException('Invalid validated payload.');
        }

        $this->languageService->updateLanguageSortOrder(
            languageId  : $languageId,
            newSortOrder: $newSortOrder
        );

        return $response->withStatus(200);
    }
}
