<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Contract;

use Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfileLegacy\Entity\ImageProfileEntity;

/**
 * Abstraction over the source of image profiles.
 *
 * The provider MUST NOT filter by `is_active` inside `findByCode`.
 * Active-state filtering is the validator's responsibility so that
 * admin tools can load inactive profiles through the same contract.
 */
interface ImageProfileProviderInterface
{
    /**
     * Resolve a profile by its stable business code.
     *
     * Returns `null` when no profile matches the given code.
     * MUST NOT throw for a missing profile.
     */
    public function findByCode(string $code): ?ImageProfileEntity;

    /**
     * Return all profiles as a typed collection (active and inactive).
     */
    public function listAll(): ImageProfileCollectionDTO;

    /**
     * Return only active profiles as a typed collection.
     * Implementations MAY apply the filter at the storage level for efficiency.
     */
    public function listActive(): ImageProfileCollectionDTO;
}
