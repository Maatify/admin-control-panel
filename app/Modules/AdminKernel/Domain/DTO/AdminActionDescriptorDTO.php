<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

final class AdminActionDescriptorDTO
{
    /**
     * @param array<string, scalar> $context
     */
    public function __construct(
        public readonly string $action,
        public readonly string $targetType,
        public readonly ?int $targetId,
        public readonly array $context
    ) {
    }
}
