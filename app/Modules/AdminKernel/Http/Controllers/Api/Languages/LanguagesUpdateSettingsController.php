<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 03:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Languages;

use Maatify\AdminKernel\Validation\Schemas\I18n\LanguageUpdateSettingsSchema;
use Maatify\I18n\Enum\TextDirectionEnum;
use Maatify\I18n\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final readonly class LanguagesUpdateSettingsController
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

        // 1) Validate
        $this->validationGuard->check(new LanguageUpdateSettingsSchema(), $body);

        // 2) Type narrowing (strict)
        $languageId = $body['language_id'];
        $directionRaw = $body['direction'];

        if (
            ! is_int($languageId)
            || ! is_string($directionRaw)
        ) {
            throw new RuntimeException('Invalid validated payload.');
        }

        $icon = null;
        if (array_key_exists('icon', $body)) {
            if (! is_string($body['icon'])) {
                throw new RuntimeException('Invalid icon value.');
            }
            $icon = $body['icon'];
        }

        // 3) Execute service (contract-safe)
        $this->languageService->updateLanguageSettings(
            languageId: $languageId,
            direction : TextDirectionEnum::from($directionRaw),
            icon      : $icon,
        );

        // 4) No Content
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
