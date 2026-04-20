<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesDropdownController
{
    public function __construct(
        private WebsiteUiThemeQueryService $queryService,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $list = $this->queryService->dropdown();

        return $this->json->data($response, ['data' => $list]);
    }
}
