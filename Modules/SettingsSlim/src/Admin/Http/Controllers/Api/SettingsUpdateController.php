<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\SettingsSlim\Admin\Domain\Validation\SettingUpdateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class SettingsUpdateController
{
    public function __construct(
        private AdminSettingService $service,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new SettingUpdateSchema(), $body);

        /**
         * @var array{
         *   setting_key: string,
         *   value: string
         * } $validated
         */
        $validated = $body;

        $this->service->updateValue(new UpdateSettingValueCommand(
            settingKey: $validated['setting_key'],
            settingValue: $validated['value']
        ));

        return $this->json->success($response);
    }
}
