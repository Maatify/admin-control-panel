<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation\WebsiteUiThemeUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\WebsiteUiTheme\Command\UpdateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeCommandService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesUpdateController
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

        $this->validationGuard->check(new WebsiteUiThemeUpdateSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var string $entityType */
        $entityType = $body['entity_type'];
        /** @var string $themeFile */
        $themeFile = $body['theme_file'];
        /** @var string $displayName */
        $displayName = $body['display_name'];

        $this->commandService->update(new UpdateWebsiteUiThemeCommand(
            id: $id,
            entityType: $entityType,
            themeFile: $themeFile,
            displayName: $displayName,
        ));

        return $this->json->success($response);
    }
}
