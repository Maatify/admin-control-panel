<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Languages;

use Maatify\AdminKernel\Validation\Schemas\I18n\LanguageUpdateNameSchema;
use Maatify\I18n\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final readonly class LanguagesUpdateNameController
{
    public function __construct(
        private LanguageManagementService $languageService,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new LanguageUpdateNameSchema(),
            $body
        );

        $languageId = $body['language_id'];
        $name = $body['name'];

        if (! is_int($languageId) || ! is_string($name)) {
            throw new RuntimeException('Invalid validated payload.');
        }

        $this->languageService->updateLanguageName(
            languageId: $languageId,
            name: $name
        );

        return $response->withStatus(200);
    }
}
