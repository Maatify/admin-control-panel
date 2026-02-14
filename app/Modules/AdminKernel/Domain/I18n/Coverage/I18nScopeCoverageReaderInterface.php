<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Coverage;

use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByDomainItemDTO;
use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByLanguageItemDTO;

interface I18nScopeCoverageReaderInterface
{
    /**
     * @return ScopeCoverageByLanguageItemDTO[]
     */
    public function getScopeCoverageByLanguage(int $scopeId): array;

    /**
     * @return ScopeCoverageByDomainItemDTO[]
     */
    public function getScopeCoverageByDomain(int $scopeId, int $languageId): array;
}
