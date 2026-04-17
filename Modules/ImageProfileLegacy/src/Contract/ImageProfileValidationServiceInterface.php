<?php

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Contract;

use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO;
use Maatify\ImageProfileLegacy\Entity\ImageProfileEntity;

/**
 * Canonical consumer-facing service contract for validation-first usage.
 *
 * Consumers should depend on this interface (or ImageProfileValidationService)
 * instead of composing ad-hoc flows with loose arrays or random object graphs.
 */
interface ImageProfileValidationServiceInterface
{
    public function findProfileByCode(string $code): ?ImageProfileEntity;

    public function listAllProfiles(): ImageProfileCollectionDTO;

    public function listActiveProfiles(): ImageProfileCollectionDTO;

    public function validateByCode(string $profileCode, ImageFileInputDTO $input): ImageValidationResultDTO;
}
