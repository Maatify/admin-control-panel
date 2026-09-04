<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation\WebsiteUiThemeEntityTypeSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesDropdownByEntityTypeController
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

        $this->validationGuard->check(new WebsiteUiThemeEntityTypeSchema(), $body);

        /** @var string $entityType */
        $entityType = $body['entity_type'];

        $list = $this->queryService->dropdownByEntityType($entityType);

        return $this->json->data($response, ['data' => $list]);
    }
}
