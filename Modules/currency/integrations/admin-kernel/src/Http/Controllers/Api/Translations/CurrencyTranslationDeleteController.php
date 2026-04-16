<?php

declare(strict_types=1);

namespace Maatify\Currency\Integration\AdminKernel\Http\Controllers\Api\Translations;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\DeleteCurrencyTranslationCommand;
use Maatify\Currency\Integration\AdminKernel\Support\Validation\CurrencyTranslationDeleteSchema;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrencyTranslationDeleteController
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
        $this->validationGuard->check(new CurrencyTranslationDeleteSchema(), $body);

        $languageId = $body['language_id'];

        if (!is_int($languageId)) {
            // Defensive guard – should never happen after validation
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $this->commandService->deleteTranslation(new DeleteCurrencyTranslationCommand(
            currencyId: $currencyId,
            languageId: $languageId
        ));

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
