<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Coverage\DTO;

use JsonSerializable;

/**
 * @phpstan-type ScopeCoverageByDomainItemArray array{
 *     domain_id: int,
 *     domain_code: string,
 *     domain_name: string,
 *     total_keys: int,
 *     translated_count: int,
 *     missing_count: int,
 *     completion_percent: float
 * }
 */
final readonly class ScopeCoverageByDomainItemDTO implements JsonSerializable
{
    public function __construct(
        public int $domainId,
        public string $domainCode,
        public string $domainName,
        public int $totalKeys,
        public int $translatedCount,
        public int $missingCount,
        public float $completionPercent
    )
    {
    }

    /**
     * @return ScopeCoverageByDomainItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'domain_id'          => $this->domainId,
            'domain_code'        => $this->domainCode,
            'domain_name'        => $this->domainName,
            'total_keys'         => $this->totalKeys,
            'translated_count'   => $this->translatedCount,
            'missing_count'      => $this->missingCount,
            'completion_percent' => $this->completionPercent,
        ];
    }
}
