<?php

/**
 * Rebuilds the derived I18n stats (maa_i18n_domain_language_summary and
 * maa_i18n_key_stats) from the authoritative maa_i18n_keys /
 * maa_i18n_translations tables.
 *
 * Run after importing translations via SQL files or whenever
 * the derived tables are out of sync.
 *
 * The rebuild itself (I18nStatsRebuilder) is I18n-owned, DB-driven and never
 * reads a Host language table (ADR-019). The script itself touches no I18n
 * table: row counts and per-code facts come from the I18n public operational
 * reads. The per-language report printed afterwards is Host composition: it
 * lists the Host languages and matches the exact-scope I18n counts by
 * language_code (no summary row = translated 0).
 *
 * Usage:
 *   php scripts/i18n_rebuild_summary.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use DI\ContainerBuilder;
use Maatify\AdminKernel\Bootstrap\Container;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\AdminControlPanel\Bootstrap\AdminEnvironmentAdapter;
use Maatify\I18n\Management\Service\I18nOperationalReadService;
use Maatify\I18n\Management\Service\I18nStatsRebuilder;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Psr\Log\LoggerInterface;

/*
|--------------------------------------------------------------------------
| 1️⃣ Load ENV (HOST responsibility)
|--------------------------------------------------------------------------
*/
try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
} catch (Throwable $e) {
    fwrite(STDERR, "ENV Load Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 2️⃣ Build Runtime Config DTO
|--------------------------------------------------------------------------
*/
try {
    $runtimeConfig = AdminRuntimeConfigDTO::fromArray(
        AdminEnvironmentAdapter::forAdminKernel($_ENV)
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Runtime Config Invalid: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 3️⃣ Bootstrap Container
|--------------------------------------------------------------------------
*/
try {
    $container = Container::create($runtimeConfig, static function (ContainerBuilder $builder): void {
        // Host language registry (names for the Host-side report) + the I18n package.
        \Maatify\LanguageCore\Bootstrap\LanguageCoreBindings::register($builder);
        \Maatify\I18n\Adapter\PhpDi\I18nBindings::register($builder);
    });
} catch (Throwable $e) {
    fwrite(STDERR, "Container Bootstrap Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 4️⃣ Resolve Dependencies & Run Rebuild
|--------------------------------------------------------------------------
*/
try {
    /** @var I18nStatsRebuilder $rebuilder */
    $rebuilder = $container->get(I18nStatsRebuilder::class);

    /** @var I18nOperationalReadService $operational */
    $operational = $container->get(I18nOperationalReadService::class);

    /** @var LanguageRepositoryInterface $languages */
    $languages = $container->get(LanguageRepositoryInterface::class);

    /** @var LoggerInterface $logger */
    $logger = $container->get(LoggerInterface::class);

    // Package-owned facts only: the rebuild and the counts come from the I18n
    // public API; this script never queries I18n tables directly.
    $before = $operational->summaryRowCount();

    $rebuilder->fullRebuild();

    $after = $operational->summaryRowCount();

    $logger->info('i18n summary rebuild completed', [
        'rows_before' => $before,
        'rows_after'  => $after,
    ]);

    echo "i18n Summary Rebuild Complete. Rows: {$before} → {$after}\n";

    // Host composition: Host languages x exact-code I18n counts.
    $totalKeys = $operational->totalKeyCount();

    $translatedByCode = [];
    foreach ($operational->translatedCountByLanguageCode() as $row) {
        if ($row->languageCode !== null) {
            $translatedByCode[$row->languageCode] = $row->count;
        }
    }

    $report = [];
    foreach ($languages->listAll()->items as $language) {
        $translated = $translatedByCode[$language->code] ?? 0;

        $report[] = [
            'name'       => $language->name,
            'id'         => $language->id,
            'translated' => $translated,
            'missing'    => $totalKeys - $translated,
            'coverage'   => $totalKeys === 0 ? 0.0 : round($translated * 100 / $totalKeys, 1),
        ];
    }

    usort(
        $report,
        static fn (array $a, array $b): int => [$b['coverage'], $a['id']] <=> [$a['coverage'], $b['id']]
    );

    echo "\n";
    echo str_pad('Language', 20) . str_pad('Keys', 10) . str_pad('Done', 10) . str_pad('Missing', 10) . "Coverage\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($report as $row) {
        echo str_pad($row['name'], 20)
            . str_pad((string) $totalKeys, 10)
            . str_pad((string) $row['translated'], 10)
            . str_pad((string) $row['missing'], 10)
            . $row['coverage'] . "%\n";
    }

} catch (Throwable $e) {
    if (isset($logger)) {
        $logger->error('i18n summary rebuild failed', [
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);
    }
    fwrite(STDERR, "Rebuild Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}
