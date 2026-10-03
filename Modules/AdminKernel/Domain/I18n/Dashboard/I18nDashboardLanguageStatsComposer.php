<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Dashboard;

use Maatify\I18n\Management\Service\I18nOperationalReadService;
use Maatify\I18n\DTO\I18nStatCountDTO;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;

/**
 * Host composition of the I18n dashboard language figures (ADR-019).
 *
 * I18n only reports exact-code counts. The Host owns the language universe and
 * display names, so it composes them here: every Host language appears, and a
 * language with no translation rows counts as translated = 0.
 */
final readonly class I18nDashboardLanguageStatsComposer
{
    public function __construct(
        private I18nOperationalReadService $stats,
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    /**
     * Coverage percent per Host language, highest first.
     *
     * @return list<I18nStatCountDTO>
     */
    public function coveragePercentByLanguage(): array
    {
        $total = $this->stats->totalKeyCount();

        return $this->compose(
            static fn (int $translated): int => $total === 0
                ? 0
                : (int) round($translated * 100 / $total)
        );
    }

    /**
     * Missing translations per Host language, highest first.
     *
     * @return list<I18nStatCountDTO>
     */
    public function missingTranslationsByLanguage(): array
    {
        $total = $this->stats->totalKeyCount();

        return $this->compose(
            static fn (int $translated): int => max(0, $total - $translated)
        );
    }

    /**
     * @param callable(int): int $metric
     * @return list<I18nStatCountDTO>
     */
    private function compose(callable $metric): array
    {
        $translatedByCode = [];

        foreach ($this->stats->translatedCountByLanguageCode() as $row) {
            if ($row->languageCode !== null) {
                $translatedByCode[$row->languageCode] = $row->count;
            }
        }

        $items = [];

        foreach ($this->languageRepository->listAll()->items as $language) {
            $items[] = new I18nStatCountDTO(
                label: $language->name,
                count: $metric($translatedByCode[$language->code] ?? 0),
            );
        }

        usort(
            $items,
            static fn (I18nStatCountDTO $a, I18nStatCountDTO $b): int => $b->count <=> $a->count
        );

        return $items;
    }
}
