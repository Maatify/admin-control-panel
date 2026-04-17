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

namespace Maatify\ImageProfile\Provider;

use Maatify\ImageProfile\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;

/**
 * In-memory provider backed by an explicit list of {@see ImageProfileEntity} objects.
 *
 * Primary use cases:
 *   - unit / integration tests  (no database required)
 *   - config-file bootstrapping (profiles defined in PHP arrays)
 *   - library demos and examples
 *
 * Look-up cost is O(n) per call. This is acceptable because profile
 * counts are small and the collection is constructed once at bootstrap.
 *
 * Active-state filtering is NOT applied inside `findByCode` — the validator
 * handles that so that admin tools can still resolve inactive profiles.
 */
final class ArrayImageProfileProvider implements ImageProfileProviderInterface
{
    /**
     * Internal map: code => ImageProfile for O(1) look-up by code.
     *
     * @var array<string, ImageProfileEntity>
     */
    private readonly array $map;

    public function __construct(ImageProfileEntity ...$profiles)
    {
        $map = [];
        foreach ($profiles as $profile) {
            $map[$profile->code] = $profile;
        }
        $this->map = $map;
    }

    /**
     * Resolve a profile by its stable business code.
     *
     * Returns `null` when no profile matches — never throws for a missing code.
     */
    public function findByCode(string $code): ?ImageProfileEntity
    {
        return $this->map[$code] ?? null;
    }

    /**
     * Return all profiles held by this provider as a typed collection.
     *
     * Call `->filterActive()` on the result to limit to active profiles.
     */
    public function listAll(): ImageProfileCollectionDTO
    {
        return new ImageProfileCollectionDTO(...array_values($this->map));
    }

    /**
     * Return only active profiles as a typed collection.
     */
    public function listActive(): ImageProfileCollectionDTO
    {
        return $this->listAll()->filterActive();
    }
}
