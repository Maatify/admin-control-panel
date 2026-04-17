<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Application\Contract;

use Maatify\ImageProfile\Application\DTO\CreateImageProfileRequest;
use Maatify\ImageProfile\Application\DTO\UpdateImageProfileRequest;
use Maatify\ImageProfile\Entity\ImageProfileEntity;

/**
 * Write-side contract for image profile persistence.
 * Canonical write inputs are library-defined DTOs:
 *   - {@see CreateImageProfileRequest}
 *   - {@see UpdateImageProfileRequest}
 * No loose arrays are part of this contract surface.
 *
 * This interface is intentionally separate from
 * {@see \Maatify\ImageProfile\Contract\ImageProfileProviderInterface},
 * which handles read-only profile resolution used by the core validator.
 *
 * Separation rationale:
 *   - The core validator needs only reads — it must not depend on write operations.
 *   - Admin CRUD services need writes — they must not be coupled to the validator.
 *   - Keeping them separate lets each side evolve independently and simplifies
 *     future library extraction (the core provider stays in the library;
 *     the repository stays in the project).
 *
 * Exception contract:
 *   - `save` must NOT throw for a duplicate code — callers check `existsByCode`
 *     first and then decide to throw {@see \Maatify\ImageProfile\Application\Exception\DuplicateProfileCodeException}.
 *   - Infrastructure errors (query failure, constraint violation) propagate
 *     as {@see \Maatify\ImageProfile\Exception\ImageProfileException} or
 *     implementation-specific subclasses.
 */
interface ImageProfileRepositoryInterface
{
    /**
     * Persist a new profile and return the fully hydrated entity.
     *
     * The returned `ImageProfile` reflects the database state after insertion,
     * including the auto-generated `id`.
     */
    public function save(CreateImageProfileRequest $request): ImageProfileEntity;

    /**
     * Update an existing profile identified by `$code` and return the
     * updated entity.
     *
     * The `code` field is immutable — it is used as the stable identifier
     * and is never changed by an update.
     *
     * @throws \Maatify\ImageProfile\Exception\ImageProfileNotFoundException
     *         if no profile matches the given code.
     */
    public function update(string $code, UpdateImageProfileRequest $request): ImageProfileEntity;

    /**
     * Set the `is_active` flag of the profile identified by `$code`.
     *
     * Returns the updated entity so callers receive the confirmed new state.
     *
     * @throws \Maatify\ImageProfile\Exception\ImageProfileNotFoundException
     *         if no profile matches the given code.
     */
    public function toggleActive(string $code, bool $isActive): ImageProfileEntity;

    /**
     * Return `true` if a profile with the given code already exists in
     * the persistence layer (regardless of active state).
     */
    public function existsByCode(string $code): bool;
}
