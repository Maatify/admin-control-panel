<?php

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\I18nStatCountDTO;

interface I18nDashboardStatsReaderInterface
{
    /** @return list<I18nStatCountDTO> */
    public function coveragePercentByLanguage(): array;

    /** @return list<I18nStatCountDTO> */
    public function missingTranslationsByLanguage(): array;

    /** @return list<I18nStatCountDTO> */
    public function keyCountByScope(): array;
}
