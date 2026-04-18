<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ImageProfile;

use Maatify\AdminKernel\Domain\ImageProfile\Validation\ImageProfileUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Service\ImageProfileCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImageProfilesUpdateController
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

        $this->validationGuard->check(new ImageProfileUpdateSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var string $code */
        $code = $body['code'];
        /** @var bool $isActive */
        $isActive = $body['is_active'];
        /** @var bool $requiresTransparency */
        $requiresTransparency = $body['requires_transparency'];

        $this->commandService->update(new UpdateImageProfileCommand(
            id: $id,
            code: $code,
            displayName: $this->nullableString($body['display_name']),
            minWidth: $this->nullableInt($body['min_width']),
            minHeight: $this->nullableInt($body['min_height']),
            maxWidth: $this->nullableInt($body['max_width']),
            maxHeight: $this->nullableInt($body['max_height']),
            maxSizeBytes: $this->nullableInt($body['max_size_bytes']),
            allowedExtensions: $this->nullableString($body['allowed_extensions']),
            allowedMimeTypes: $this->nullableString($body['allowed_mime_types']),
            isActive: $isActive,
            notes: $this->nullableString($body['notes']),
            minAspectRatio: $this->nullableString($body['min_aspect_ratio']),
            maxAspectRatio: $this->nullableString($body['max_aspect_ratio']),
            requiresTransparency: $requiresTransparency,
            preferredFormat: $this->nullableString($body['preferred_format']),
            preferredQuality: $this->nullableInt($body['preferred_quality']),
            variants: $this->nullableString($body['variants']),
        ));

        return $this->json->success($response);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }
}
