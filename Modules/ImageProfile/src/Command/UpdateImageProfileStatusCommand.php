<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Command;

/**
 * Toggles active / inactive status for an image profile.
 */
final readonly class UpdateImageProfileStatusCommand
{
    public function __construct(
        public int $id,
        public bool $isActive,
    ) {}
}
