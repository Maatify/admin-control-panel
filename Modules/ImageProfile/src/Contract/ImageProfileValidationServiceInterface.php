<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\ImageValidationRequestDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;

/**
 * Site-facing validation entry point.
 */
interface ImageProfileValidationServiceInterface
{
    public function validateByCode(string $profileCode, ImageValidationRequestDTO $request): ImageValidationResultDTO;
}
