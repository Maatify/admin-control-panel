<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 16:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n;

use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\Validation\LanguageTranslationValueDeleteSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class LanguageTranslationDeleteController
{
    public function __construct(
        private TranslationWriteService $translationWriteService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    )
    {
    }

    /**
     * @param array{language_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $languageId = (int) $args['language_id'];

        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate payload
        /** @var array{key_id: int} $body */
        $this->validationGuard->check(new LanguageTranslationValueDeleteSchema(), $body);

        // 2) Call domain service only
        $this->translationWriteService->deleteTranslation(
            languageId: $languageId,
            keyId     : $body['key_id']
        );

        // 3) Response
        return $this->json->success($response);

    }
}
