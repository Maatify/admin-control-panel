<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Command\UpdateCountryCommand;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CountryUpdateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CountriesUpdateController
{
    public function __construct(
        private GeoCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CountryUpdateSchema(), $body);

        $id       = $body['id'];
        $code     = $body['code'];
        $name     = $body['name'];
        $isActive = $body['is_active'];

        if (!is_int($id) || !is_string($code) || !is_string($name) || !is_bool($isActive)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $phoneCode = null;
        if (array_key_exists('phone_code', $body) && is_string($body['phone_code'])) {
            $phoneCode = $body['phone_code'];
        }

        $currency = null;
        if (array_key_exists('currency', $body) && is_string($body['currency'])) {
            $currency = $body['currency'];
        }

        $icon = null;
        if (array_key_exists('icon', $body) && is_string($body['icon'])) {
            $icon = $body['icon'];
        }

        $isStateRequired = null;
        if (array_key_exists('is_state_required', $body)) {
            if (!is_bool($body['is_state_required'])) {
                throw new \RuntimeException('Invalid is_state_required payload.');
            }
            $isStateRequired = $body['is_state_required'];
        }

        $isPostcodeRequired = null;
        if (array_key_exists('is_postcode_required', $body)) {
            if (!is_bool($body['is_postcode_required'])) {
                throw new \RuntimeException('Invalid is_postcode_required payload.');
            }
            $isPostcodeRequired = $body['is_postcode_required'];
        }

        // 2) Execute service
        $this->commandService->updateCountry(new UpdateCountryCommand(
            id:                 $id,
            code:               $code,
            name:               $name,
            phoneCode:          $phoneCode,
            currency:           $currency,
            icon:               $icon,
            isActive:           $isActive,
            isStateRequired:    $isStateRequired,
            isPostcodeRequired: $isPostcodeRequired,
        ));

        // 3) Return success
        return $this->json->success($response);
    }
}

