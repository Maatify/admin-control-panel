<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation\WebsiteUiThemeDetailsSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesDetailsController
{
    public function __construct(
        private WebsiteUiThemeQueryService $queryService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new WebsiteUiThemeDetailsSchema(), $body);

        /** @var int $id */
        $id = $body['id'];

        return $this->json->data($response, $this->queryService->getById($id));
    }
}
