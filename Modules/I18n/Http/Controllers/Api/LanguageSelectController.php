<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 17:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Http\Controllers\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Maatify\I18n\Contract\LanguageRepositoryInterface;
use Maatify\I18n\Contract\LanguageSettingsRepositoryInterface;

final readonly class LanguageSelectController
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    )
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        $languages = $this->languageRepository->listActiveForSelect();

        $data = [];

        foreach ($languages->items as $language) {
            $settings = $this->settingsRepository->getByLanguageId($language->id);

            // Safety rule:
            // Language without settings MUST NOT appear in UI select
            if ($settings === null) {
                continue;
            }

            $data[] = [
                'id'         => $language->id,
                'code'       => $language->code,
                'name'       => $language->name,
                'direction'  => $settings->direction->value,
                'icon'       => $settings->icon,
                'is_default' => $language->fallbackLanguageId === null,
            ];
        }

        $payload = json_encode([
            'data' => $data,
        ], JSON_THROW_ON_ERROR);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
