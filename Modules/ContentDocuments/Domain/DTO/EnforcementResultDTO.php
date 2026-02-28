<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class EnforcementResultDTO
{
    /**
     * @param list<RequiredAcceptanceDTO> $requiredDocuments
     */
    public function __construct(
        public bool $requiresAcceptance,
        public array $requiredDocuments,
    ) {
    }
}
