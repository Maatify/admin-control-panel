<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class SettingsDropdownController
{
    public function __construct(
        private AdminSettingService $service,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $result = $this->service->listAsKeyValue();
        return $this->json->data($response, $result);
    }
}
