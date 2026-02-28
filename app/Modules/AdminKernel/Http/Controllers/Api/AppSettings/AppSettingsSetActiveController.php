<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\AppSettings;

use Maatify\AdminKernel\Domain\AppSettings\Validation\AppSettingsSetActiveSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AppSettings\AppSettingsServiceInterface;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AppSettingsSetActiveController
{
    public function __construct(
        private AppSettingsServiceInterface $service,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate
        $this->validationGuard->check(
            new AppSettingsSetActiveSchema(),
            $body
        );

        /**
         * @var array{
         *   setting_group: string,
         *   setting_key: string,
         *   is_active: bool
         * } $body
         */

        // 2) DTO
        $keyDto = new AppSettingKeyDTO(
            group: $body['setting_group'],
            key: $body['setting_key']
        );

        // 3) Execute domain service
        $this->service->setActive(
            $keyDto,
            (bool)$body['is_active']
        );

        // 4) Response
        return $this->json->data($response, ['status' => 'ok']);
    }
}
