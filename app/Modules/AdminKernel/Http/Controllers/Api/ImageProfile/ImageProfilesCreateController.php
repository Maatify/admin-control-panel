<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ImageProfile;

use Maatify\AdminKernel\Domain\ImageProfile\Validation\ImageProfileCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Service\ImageProfileCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImageProfilesCreateController
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

        $this->validationGuard->check(new ImageProfileCreateSchema(), $body);

        /** @var string $code */
        $code = $body['code'];

        $this->commandService->create(new CreateImageProfileCommand(
            code: $code,
            displayName: $this->nullableString($body, 'display_name'),
            minWidth: $this->nullableInt($body, 'min_width'),
            minHeight: $this->nullableInt($body, 'min_height'),
            maxWidth: $this->nullableInt($body, 'max_width'),
            maxHeight: $this->nullableInt($body, 'max_height'),
            maxSizeBytes: $this->nullableInt($body, 'max_size_bytes'),
            allowedExtensions: $this->nullableString($body, 'allowed_extensions'),
            allowedMimeTypes: $this->nullableString($body, 'allowed_mime_types'),
            isActive: $this->boolWithDefault($body, 'is_active', true),
            notes: $this->nullableString($body, 'notes'),
            minAspectRatio: $this->nullableString($body, 'min_aspect_ratio'),
            maxAspectRatio: $this->nullableString($body, 'max_aspect_ratio'),
            requiresTransparency: $this->boolWithDefault($body, 'requires_transparency', false),
            preferredFormat: $this->nullableString($body, 'preferred_format'),
            preferredQuality: $this->nullableInt($body, 'preferred_quality'),
            variants: $this->nullableString($body, 'variants'),
        ));

        return $this->json->success($response);
    }

    /** @param array<string, mixed> $body */
    private function nullableString(array $body, string $key): ?string
    {
        if (!array_key_exists($key, $body) || $body[$key] === null) {
            return null;
        }

        return is_string($body[$key]) ? $body[$key] : null;
    }

    /** @param array<string, mixed> $body */
    private function nullableInt(array $body, string $key): ?int
    {
        if (!array_key_exists($key, $body) || $body[$key] === null) {
            return null;
        }

        return is_int($body[$key]) ? $body[$key] : null;
    }

    /** @param array<string, mixed> $body */
    private function boolWithDefault(array $body, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $body)) {
            return $default;
        }

        return is_bool($body[$key]) ? $body[$key] : $default;
    }
}
