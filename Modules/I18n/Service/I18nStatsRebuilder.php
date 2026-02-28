<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 02:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 * @note        Kernel-grade rebuild for DERIVED i18n stats tables.
 *               - i18n_domain_language_summary (domain-first summary)
 *               - i18n_key_stats (per-key counters)
 *
 *               Guarantees:
 *               - DB-driven rebuild (no PHP loops, no N+1)
 *               - Single TX (truncate + rebuild) to avoid half-state
 *               - Non-authoritative tables: safe to fully rebuild any time
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface;
use Maatify\I18n\Contract\I18nTransactionManagerInterface;
use Maatify\I18n\Contract\KeyStatsRepositoryInterface;

final readonly class I18nStatsRebuilder
{
    public function __construct(
        private I18nTransactionManagerInterface $tx,
        private DomainLanguageSummaryRepositoryInterface $summaryRepository,
        private KeyStatsRepositoryInterface $keyStatsRepository,
    ) {
    }

    /**
     * Full rebuild for BOTH derived layers.
     *
     * This MUST be DB-driven (INSERT..SELECT / GROUP BY) inside repositories.
     *
     * Required repository ops:
     * - DomainLanguageSummaryRepositoryInterface::truncate()
     * - DomainLanguageSummaryRepositoryInterface::rebuildAll()
     * - KeyStatsRepositoryInterface::truncate()
     * - KeyStatsRepositoryInterface::rebuildAll()
     */
    public function fullRebuild(): void
    {
        $this->tx->run(function (): void {

            // 1) Clear derived tables
            $this->summaryRepository->truncate();
            $this->keyStatsRepository->truncate();

            // 2) Rebuild derived tables
            $this->summaryRepository->rebuildAll();
            $this->keyStatsRepository->rebuildAll();
        });
    }
}
