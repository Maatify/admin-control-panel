<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation\WebsiteUiThemeCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\WebsiteUiTheme\Command\CreateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeCommandService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesCreateController
{
    public function __construct(
        private WebsiteUiThemeCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new WebsiteUiThemeCreateSchema(), $body);

        /** @var string $entityType */
        $entityType = $body['entity_type'];
        /** @var string $themeFile */
        $themeFile = $body['theme_file'];
        /** @var string $displayName */
        $displayName = $body['display_name'];

        $this->commandService->create(new CreateWebsiteUiThemeCommand(
            entityType: $entityType,
            themeFile: $themeFile,
            displayName: $displayName,
        ));

        return $this->json->success($response);
    }
}
