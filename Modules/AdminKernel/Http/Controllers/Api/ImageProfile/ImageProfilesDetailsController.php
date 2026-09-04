<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ImageProfile;

use Maatify\AdminKernel\Domain\ImageProfile\Validation\ImageProfileDetailsSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ImageProfile\Service\ImageProfileQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImageProfilesDetailsController
{
    public function __construct(
        private ImageProfileQueryService $queryService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new ImageProfileDetailsSchema(), $body);

        /** @var int $id */
        $id = $body['id'];

        return $this->json->data($response, $this->queryService->getById($id));
    }
}
