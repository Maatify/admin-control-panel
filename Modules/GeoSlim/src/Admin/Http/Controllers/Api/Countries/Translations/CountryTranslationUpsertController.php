<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\Translations;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Command\UpsertCountryTranslationCommand;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CountryTranslationUpsertSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CountryTranslationUpsertController
{
    public function __construct(
        private GeoCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $countryId = (int) $args['country_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CountryTranslationUpsertSchema(), $body);

        $languageId     = $body['language_id'];
        $translatedName = $body['translated_name'];

        if (!is_int($languageId) || !is_string($translatedName)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 2) Execute service
        $this->commandService->upsertCountryTranslation(new UpsertCountryTranslationCommand(
            countryId:      $countryId,
            languageId:     $languageId,
            translatedName: $translatedName,
        ));

        // 3) Return success
        return $this->json->success($response);
    }
}

