<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Command;

/**
 * Toggles active / inactive status for an image profile.
 */
final class UpdateImageProfileStatusCommand
{
    public function __construct(
        public readonly int $id,
        public readonly bool $isActive,
    ) {}
}
