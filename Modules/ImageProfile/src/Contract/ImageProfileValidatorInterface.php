<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\DTO\ImageValidationResultDTO;

/**
 * Core validation entry point.
 *
 * Implementations MUST:
 *   - return an {@see ImageValidationResultDTO} for every call
 *   - NOT throw for business validation failures
 *   - collect every applicable error (do not stop at the first one) so
 *     that the admin UI / API client can show all failing rules at once
 *
 * Exceptions are reserved for infrastructure failures, impossible states,
 * or API misuse (see {@see \Maatify\ImageProfile\Exception\ImageProfileException}).
 */
interface ImageProfileValidatorInterface
{
    public function validateByCode(
        string            $profileCode,
        ImageFileInputDTO $input,
    ): ImageValidationResultDTO;
}
