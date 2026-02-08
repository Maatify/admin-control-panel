<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n;

use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey\TranslationKeyUpdateDescriptionSchema;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final readonly class TranslationKeysUpdateDescriptionController
{
    public function __construct(
        private TranslationWriteService $translationWriteService,
        private ValidationGuard $validationGuard
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new TranslationKeyUpdateDescriptionSchema(),
            $body
        );

        $keyId = $body['key_id'];
        $description = $body['description'];

        if (! is_int($keyId) || ! is_string($description)) {
            throw new RuntimeException('Invalid validated payload.');
        }

        $this->translationWriteService->updateKeyDescription(
            keyId: $keyId,
            description: $description
        );

        $response->getBody()->write(
            json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
