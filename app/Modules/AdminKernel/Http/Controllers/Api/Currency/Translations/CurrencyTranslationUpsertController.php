<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency\Translations;

use Maatify\AdminKernel\Domain\Currency\Validation\CurrencyTranslationUpsertSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\UpsertCurrencyTranslationCommand;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrencyTranslationUpsertController
{
    public function __construct(
        private CurrencyCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $currencyId = (int) $args['currency_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CurrencyTranslationUpsertSchema(), $body);

        $languageId = $body['language_id'];
        $translatedName = $body['translated_name'];

        if (!is_int($languageId) || !is_string($translatedName)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $this->commandService->upsertTranslation(new UpsertCurrencyTranslationCommand(
            currencyId: $currencyId,
            languageId: $languageId,
            translatedName: $translatedName
        ));

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
