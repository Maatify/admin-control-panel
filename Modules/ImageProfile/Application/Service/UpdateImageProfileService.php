<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Application\Service;

use Maatify\ImageProfile\Application\Contract\ImageProfileRepositoryInterface;
use Maatify\ImageProfile\Application\DTO\UpdateImageProfileRequest;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;

/**
 * Update an existing image profile's mutable fields.
 *
 * The profile `code` is immutable — it is used as the stable lookup key
 * and is never changed. Any attempt to change a code must go through
 * a delete + create flow, which is intentionally not supported in v1.
 *
 * Responsibilities:
 *   1. Delegate persistence to the repository (which enforces "not found").
 *   2. Return the updated entity as confirmed by the persistence layer.
 */
final class UpdateImageProfileService
{
    public function __construct(
        private readonly ImageProfileRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ImageProfileNotFoundException if no profile matches the given code.
     */
    public function execute(string $code, UpdateImageProfileRequest $request): ImageProfileEntity
    {
        return $this->repository->update($code, $request);
    }
}
