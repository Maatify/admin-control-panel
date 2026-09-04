<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation\WebsiteUiThemeDeleteSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\WebsiteUiTheme\Command\DeleteWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeCommandService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesDeleteController
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

        $this->validationGuard->check(new WebsiteUiThemeDeleteSchema(), $body);

        /** @var int $id */
        $id = $body['id'];

        $this->commandService->delete(new DeleteWebsiteUiThemeCommand($id));

        return $this->json->success($response);
    }
}
