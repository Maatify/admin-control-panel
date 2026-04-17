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

namespace Maatify\ImageProfile\Application\Service;

use Maatify\ImageProfile\Application\Contract\ImageProfileRepositoryInterface;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;

/**
 * Enable or disable an image profile.
 *
 * Disabling a profile causes the core validator to reject any image
 * submitted against it with a `profile_inactive` error — without
 * deleting the profile definition.
 *
 * Both `enable()` and `disable()` are convenience wrappers over `toggle()`
 * with explicit boolean intent, making call sites read clearly.
 */
final class ToggleImageProfileService
{
    public function __construct(
        private readonly ImageProfileRepositoryInterface $repository,
    ) {
    }

    /**
     * Enable the profile identified by `$code`.
     *
     * @throws ImageProfileNotFoundException if no profile matches the given code.
     */
    public function enable(string $code): ImageProfileEntity
    {
        return $this->repository->toggleActive($code, true);
    }

    /**
     * Disable the profile identified by `$code`.
     *
     * @throws ImageProfileNotFoundException if no profile matches the given code.
     */
    public function disable(string $code): ImageProfileEntity
    {
        return $this->repository->toggleActive($code, false);
    }

    /**
     * Set `is_active` explicitly.
     *
     * Prefer `enable()` / `disable()` at call sites for clarity.
     *
     * @throws ImageProfileNotFoundException if no profile matches the given code.
     */
    public function toggle(string $code, bool $isActive): ImageProfileEntity
    {
        return $this->repository->toggleActive($code, $isActive);
    }
}
