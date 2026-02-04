<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 18:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security;

/**
 * Represents authorization requirements for a route.
 *
 * - anyOf: user must have at least one permission
 * - allOf: user must have all permissions
 */
final readonly class PermissionRequirement
{
    /** @var list<string> */
    public array $anyOf;

    /** @var list<string> */
    public array $allOf;

    /**
     * @param array<int|string, string> $anyOf
     * @param array<int|string, string> $allOf
     */
    public function __construct(
        array $anyOf = [],
        array $allOf = [],
    ) {
        // Normalize → list<string>
        /** @var list<string> $any */
        $any = array_values($anyOf);

        /** @var list<string> $all */
        $all = array_values($allOf);

        $this->anyOf = $any;
        $this->allOf = $all;
    }

    public static function single(string $permission): self
    {
        return new self([$permission], []);
    }

    public static function anyOf(string ...$permissions): self
    {
        return new self($permissions, []);
    }

    public static function allOf(string ...$permissions): self
    {
        return new self([], $permissions);
    }
}
