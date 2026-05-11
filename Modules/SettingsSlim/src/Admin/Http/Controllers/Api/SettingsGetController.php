<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\SettingsSlim\Admin\Domain\Validation\SettingGetSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class SettingsGetController
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

        $this->validationGuard->check(new SettingGetSchema(), $body);

        /**
         * @var array{
         *   setting_key: string
         * } $validated
         */
        $validated = $body;

        if (!is_string($validated['setting_key'])) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $setting = $this->service->getByKey($validated['setting_key']);

        return $this->json->data($response, $setting);
    }
}
