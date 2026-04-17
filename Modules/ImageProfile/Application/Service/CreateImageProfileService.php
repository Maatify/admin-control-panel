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
use Maatify\ImageProfile\Application\DTO\CreateImageProfileRequest;
use Maatify\ImageProfile\Application\Exception\DuplicateProfileCodeException;
use Maatify\ImageProfile\Entity\ImageProfileEntity;

/**
 * Create a new image profile.
 *
 * Responsibilities:
 *   1. Guard against duplicate codes (business rule).
 *   2. Delegate persistence to the repository.
 *   3. Return the fully hydrated entity as confirmed by the persistence layer.
 *
 * This service must NOT contain validation logic (min/max rules, MIME checks).
 * That responsibility belongs to {@see \Maatify\ImageProfile\Validator\ImageProfileValidator}.
 */
final class CreateImageProfileService
{
    public function __construct(
        private readonly ImageProfileRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws DuplicateProfileCodeException if the code is already in use.
     */
    public function execute(CreateImageProfileRequest $request): ImageProfileEntity
    {
        if ($this->repository->existsByCode($request->code)) {
            throw DuplicateProfileCodeException::forCode($request->code);
        }

        return $this->repository->save($request);
    }
}
