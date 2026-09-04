<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-05 10:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\AppSettings;

use Maatify\AdminKernel\Domain\AppSettings\Validation\AppSettingsCreateSchema;
use Maatify\AppSettings\AppSettingsServiceInterface;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AppSettings\Enum\AppSettingValueTypeEnum;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class AppSettingsCreateController
{
    public function __construct(
        private AppSettingsServiceInterface $service,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new AppSettingsCreateSchema(), $body);

        /**
         * @var array{
         *   setting_group: string,
         *   setting_key: string,
         *   setting_value: string,
         *   setting_type?: string,
         *   is_active?: bool
         * } $body
         */

        $valueType = AppSettingValueTypeEnum::STRING;
        if (!empty($body['setting_type'])) {
            $valueType = AppSettingValueTypeEnum::tryFrom($body['setting_type']) ?? AppSettingValueTypeEnum::STRING;
        }

        // 2) Build DTO (existing DTO)
        $dto = new AppSettingDTO(
            group   : $body['setting_group'],
            key     : $body['setting_key'],
            value   : $body['setting_value'],
            valueType: $valueType,
            isActive: $body['is_active'] ?? true
        );

        // 3) Delegate to domain service
        $this->service->create($dto);

        // 4) Response
        return $this->json->data($response, ['status' => 'ok']);
    }
}
