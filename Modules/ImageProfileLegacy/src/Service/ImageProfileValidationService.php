<?php

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Service;

use Maatify\ImageProfileLegacy\Contract\ImageMetadataReaderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileValidationServiceInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileValidatorInterface;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO;
use Maatify\ImageProfileLegacy\Entity\ImageProfileEntity;
use Maatify\ImageProfileLegacy\Validator\ImageProfileValidator;

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
