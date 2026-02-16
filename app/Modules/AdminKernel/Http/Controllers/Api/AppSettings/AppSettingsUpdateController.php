<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-05 10:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\AppSettings;

use Maatify\AdminKernel\Domain\AppSettings\Validation\AppSettingsUpdateSchema;
use Maatify\AppSettings\AppSettingsServiceInterface;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\Enum\AppSettingValueTypeEnum;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AppSettingsUpdateController
{
    public function __construct(
        private AppSettingsServiceInterface $service,
        private ValidationGuard $validationGuard
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate
        $this->validationGuard->check(
            new AppSettingsUpdateSchema(),
            $body
        );

        /**
         * @var array{
         *   setting_group: string,
         *   setting_key: string,
         *   setting_value: string,
         *   setting_type?: string
         * } $body
         */

        $valueType = null;
        if (!empty($body['setting_type'])) {
            $valueType = AppSettingValueTypeEnum::tryFrom($body['setting_type']);
        }

        // 2) DTO
        $dto = new AppSettingUpdateDTO(
            group: $body['setting_group'],
            key  : $body['setting_key'],
            value: $body['setting_value'],
            valueType: $valueType
        );

        // 3) Execute domain service
        $this->service->update($dto);

        // 4) Response
        $response->getBody()->write(
            json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
