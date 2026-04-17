<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ImageProfile;

use Maatify\AdminKernel\Domain\ImageProfile\Validation\ImageProfileSetActiveSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\Service\ImageProfileCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImageProfilesSetActiveController
{
    public function __construct(
        private ImageProfileCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new ImageProfileSetActiveSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var bool $isActive */
        $isActive = $body['is_active'];

        $this->commandService->updateStatus(new UpdateImageProfileStatusCommand(
            id: $id,
            isActive: $isActive,
        ));

        return $this->json->success($response);
    }
}
