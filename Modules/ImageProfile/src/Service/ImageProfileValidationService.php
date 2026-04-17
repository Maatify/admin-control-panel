<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Service;

use Maatify\ImageProfile\Contract\ImageMetadataReaderInterface;
use Maatify\ImageProfile\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfile\Contract\ImageProfileValidationServiceInterface;
use Maatify\ImageProfile\Contract\ImageProfileValidatorInterface;
use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Validator\ImageProfileValidator;

/**
 * Neutral consumer-facing service for profile lookup and validation.
 *
 * This class is the recommended public entry point for normal library usage.
 * It composes provider + metadata reader + validator without introducing any
 * host-application concerns (controllers, upload orchestration, storage flow).
 *
 * Scope:
 *   - resolve profiles by code
 *   - list profiles
 *   - validate an input image against a profile
 *
 * Out of scope:
 *   - upload lifecycle
 *   - storage/CDN orchestration
 *   - image processing / variant generation
 */
final class ImageProfileValidationService implements ImageProfileValidationServiceInterface
{
    public function __construct(
        private readonly ImageProfileProviderInterface $provider,
        private readonly ImageProfileValidatorInterface $validator,
    ) {
    }

    /**
     * Convenience constructor for the common composition path.
     */
    public static function compose(
        ImageProfileProviderInterface $provider,
        ImageMetadataReaderInterface $reader,
    ): self {
        return new self(
            provider: $provider,
            validator: new ImageProfileValidator($provider, $reader),
        );
    }

    public function findProfileByCode(string $code): ?ImageProfileEntity
    {
        return $this->provider->findByCode($code);
    }

    public function listAllProfiles(): ImageProfileCollectionDTO
    {
        return $this->provider->listAll();
    }

    public function listActiveProfiles(): ImageProfileCollectionDTO
    {
        return $this->provider->listActive();
    }

    public function validateByCode(string $profileCode, ImageFileInputDTO $input): ImageValidationResultDTO
    {
        return $this->validator->validateByCode($profileCode, $input);
    }
}
