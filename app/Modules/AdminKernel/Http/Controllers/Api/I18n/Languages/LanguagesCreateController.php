<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 03:04
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages;

use Maatify\AdminKernel\Validation\Schemas\I18n\Language\LanguageCreateSchema;
use Maatify\I18n\Enum\TextDirectionEnum;
use Maatify\I18n\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class LanguagesCreateController
{
    public function __construct(
        private LanguageManagementService $languageService,
        private ValidationGuard $validationGuard
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new LanguageCreateSchema(), $body);

        // 2) Explicit type narrowing (phpstan-safe)
        $name = $body['name'];
        $code = $body['code'];
        $directionRaw = $body['direction'];

        if (!is_string($name) || !is_string($code) || !is_string($directionRaw)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        $icon = null;
        if (array_key_exists('icon', $body)) {
            if (!is_string($body['icon'])) {
                throw new \RuntimeException('Invalid icon value.');
            }
            $icon = $body['icon'];
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new \RuntimeException('Invalid is_active value.');
            }
            $isActive = $body['is_active'];
        }

        $fallbackLanguageId = null;
        if (array_key_exists('fallback_language_id', $body)) {
            if (!is_int($body['fallback_language_id'])) {
                throw new \RuntimeException('Invalid fallback_language_id value.');
            }
            $fallbackLanguageId = $body['fallback_language_id'];
        }

        // 3) Execute service
        $this->languageService->createLanguage(
            name: $name,
            code: $code,
            direction: TextDirectionEnum::from($directionRaw),
            icon: $icon,
            isActive: $isActive,
            fallbackLanguageId: $fallbackLanguageId
        );

        // 4) No Content
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
