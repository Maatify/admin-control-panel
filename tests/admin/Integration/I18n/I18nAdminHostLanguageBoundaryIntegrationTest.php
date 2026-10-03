<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use DateTimeZone;
use Maatify\AdminKernel\Domain\I18n\Dashboard\I18nDashboardLanguageStatsComposer;
use Maatify\AdminKernel\Infrastructure\I18n\Language\PdoLanguageCodeChange;
use Maatify\AdminKernel\Domain\I18n\Language\LanguageCodeResolver;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Infrastructure\I18n\Reader\PackageI18nLanguageTranslationValueReader;
use Maatify\AdminKernel\Infrastructure\I18n\Reader\PackageI18nScopeCoverageReader;
use Maatify\AdminKernel\Infrastructure\I18n\Reader\PackageI18nTranslationsReader;
use Maatify\AdminKernel\Infrastructure\Repository\I18n\Languages\PdoLanguageLookup;
use Maatify\I18n\Exception\InvalidLanguageCodeException;
use Maatify\I18n\Exception\LanguageCodeAlreadyInUseException;
use Maatify\I18n\Repository\Mysql\MysqlDomainLanguageSummaryRepository;
use Maatify\I18n\Repository\Mysql\MysqlDomainRepository;
use Maatify\I18n\Repository\Mysql\MysqlDomainScopeRepository;
use Maatify\I18n\Repository\Mysql\MysqlI18nOperationalStatsRepository;
use Maatify\I18n\Repository\Mysql\MysqlKeyStatsRepository;
use Maatify\I18n\Repository\Mysql\MysqlScopeRepository;
use Maatify\I18n\Repository\Mysql\MysqlTranslationKeyRepository;
use Maatify\I18n\Repository\Mysql\MysqlTranslationQueryRepository;
use Maatify\I18n\Repository\Mysql\MysqlTranslationRepository;
use Maatify\I18n\Service\I18nGovernancePolicyService;
use Maatify\I18n\Service\MissingCounterService;
use Maatify\I18n\Consumer\Service\TranslationReadService;
use Maatify\I18n\Management\Command\CreateKeyCommand;
use Maatify\I18n\Management\Command\UpsertTranslationCommand;
use Maatify\I18n\Management\Service\I18nManagementReadService;
use Maatify\I18n\Management\Service\I18nOperationalReadService;
use Maatify\I18n\Management\Service\TranslationWriteService;
use Maatify\LanguageCore\Enum\TextDirectionEnum;
use Maatify\LanguageCore\Exception\LanguageNotFoundException;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;
use Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageRepository;
use Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageSettingsRepository;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use Maatify\SharedCommon\Infrastructure\SystemClock;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

/**
 * Admin Host side of the ADR-019 language boundary, on a real database:
 * the Host resolves the LanguageCore ID to the exact code, composes its own
 * language list with I18n exact-scope counts by code, and orchestrates the
 * atomic language-code rename.
 */
final class I18nAdminHostLanguageBoundaryIntegrationTest extends TestCase
{
    private const CODES = ['zt', 'zu', 'zv', 'zt2', 'zx', 'orph'];

    private PDO $pdo;
    private LanguageManagementService $languageManagement;
    private MysqlLanguageRepository $languageRepository;
    private TranslationWriteService $writer;
    private TranslationReadService $reader;
    private MysqlDomainLanguageSummaryRepository $summary;
    private I18nManagementReadService $management;
    private I18nOperationalReadService $operational;
    private PdoLanguageLookup $languageLookup;
    private int $scopeId;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();

        $schema = file_get_contents(dirname(__DIR__, 4) . '/vendor/maatify/php-i18n/schema/schema.i18n.sql');
        self::assertIsString($schema);
        $this->pdo->exec($schema);

        $this->cleanupLanguages();

        $this->pdo->exec("INSERT INTO maa_i18n_scopes (code, name) VALUES ('ct', 'Website')");
        $this->scopeId = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO maa_i18n_domains (code, name) VALUES ('home', 'Home')");
        $this->pdo->exec("INSERT INTO maa_i18n_domain_scopes (scope_code, domain_code) VALUES ('ct', 'home')");

        $this->languageRepository = new MysqlLanguageRepository($this->pdo);
        $this->languageManagement = new LanguageManagementService(
            $this->languageRepository,
            new MysqlLanguageSettingsRepository($this->pdo)
        );

        $clock = new SystemClock(new DateTimeZone('UTC'));
        $keys = new MysqlTranslationKeyRepository($this->pdo);
        $translations = new MysqlTranslationRepository($this->pdo, $clock);
        $this->summary = new MysqlDomainLanguageSummaryRepository($this->pdo);
        $scopes = new MysqlScopeRepository($this->pdo);
        $domains = new MysqlDomainRepository($this->pdo);
        $domainScopes = new MysqlDomainScopeRepository($this->pdo);
        $policy = new I18nGovernancePolicyService($scopes, $domains, $domainScopes);
        $counter = new MissingCounterService($this->summary, $keys, new MysqlKeyStatsRepository($this->pdo));
        $this->writer = new TranslationWriteService(
            new PdoTransactionRunner($this->pdo),
            $keys,
            $translations,
            $policy,
            $counter
        );
        $this->reader = new TranslationReadService($keys, $translations);
        $this->management = new I18nManagementReadService(
            $scopes,
            $domains,
            $domainScopes,
            $keys,
            new MysqlTranslationQueryRepository($this->pdo)
        );
        $this->operational = new I18nOperationalReadService(new MysqlI18nOperationalStatsRepository($this->pdo));
        $this->languageLookup = new PdoLanguageLookup($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->cleanupLanguages();
    }

    public function testCoverageComposesHostLanguagesWithI18nCountsByCodeAndShowsZeroTranslationLanguages(): void
    {
        $zt = $this->createLanguage('zt');
        $zu = $this->createLanguage('zu');
        $zv = $this->createLanguage('zv'); // never translated: no I18n summary row at all

        $a = $this->createKey('ct', 'home', 'a');
        $b = $this->createKey('ct', 'home', 'b');
        $this->createKey('ct', 'home', 'c');
        $this->upsert('zt', $a, 'x');
        $this->upsert('zt', $b, 'x');
        $this->upsert('zu', $a, 'x');

        self::assertNull($this->summary->getRow('ct', 'home', 'zv'), 'I18n must not pre-create rows for Host languages');

        $reader = new PackageI18nScopeCoverageReader($this->management, $this->operational, $this->languageLookup);
        $byCode = [];
        foreach ($reader->getScopeCoverageByLanguage($this->scopeId) as $item) {
            $byCode[$item->languageCode] = $item;
        }

        self::assertArrayHasKey('zv', $byCode, 'a Host language without an I18n summary row must still be listed');
        self::assertSame([$zt, 2, 3, 1], [$byCode['zt']->languageId, $byCode['zt']->translatedCount, $byCode['zt']->totalKeys, $byCode['zt']->missingCount]);
        self::assertSame([$zu, 1, 3, 2], [$byCode['zu']->languageId, $byCode['zu']->translatedCount, $byCode['zu']->totalKeys, $byCode['zu']->missingCount]);
        self::assertSame([$zv, 0, 3, 3], [$byCode['zv']->languageId, $byCode['zv']->translatedCount, $byCode['zv']->totalKeys, $byCode['zv']->missingCount]);
        self::assertSame(0.0, $byCode['zv']->completionPercent);

        $byDomain = $reader->getScopeCoverageByDomain($this->scopeId, $zv);
        self::assertCount(1, $byDomain);
        self::assertSame([3, 0, 3], [$byDomain[0]->totalKeys, $byDomain[0]->translatedCount, $byDomain[0]->missingCount]);

        $byDomainZt = $reader->getScopeCoverageByDomain($this->scopeId, $zt);
        self::assertSame([3, 2, 1], [$byDomainZt[0]->totalKeys, $byDomainZt[0]->translatedCount, $byDomainZt[0]->missingCount]);

        self::assertSame([], $reader->getScopeCoverageByDomain($this->scopeId, 987654), 'unknown Host language ID');
    }

    public function testTranslationManagementResolvesRouteIdToExactCodeBeforeCallingI18n(): void
    {
        $zt = $this->createLanguage('zt');
        $zu = $this->createLanguage('zu');
        $resolver = new LanguageCodeResolver($this->languageRepository);

        $a = $this->createKey('ct', 'home', 'a');

        // Controller flow: route ID -> Host resolves code -> I18n gets the code only.
        $this->upsert($resolver->resolveCode($zt), $a, 'zt-value');
        $this->upsert($resolver->resolveCode($zu), $a, 'zu-value');

        self::assertSame('zt-value', $this->reader->getValue('zt', 'ct', 'home', 'a'));
        self::assertSame('zu-value', $this->reader->getValue('zu', 'ct', 'home', 'a'));

        // The Admin values grid is keyed by the Host ID and resolves to the same exact scope.
        $grid = new PackageI18nLanguageTranslationValueReader($this->management, $this->languageLookup);
        $result = $grid->queryTranslationValues(
            $zt,
            new ListQueryDTO(1, 20, null, [], null, null),
            new ResolvedListFilters(null, [], null, null)
        );
        self::assertCount(1, $result->data);
        self::assertSame('zt-value', $result->data[0]->value);
        self::assertSame($zt, $result->data[0]->languageId);

        try {
            $grid->queryTranslationValues(
                987654,
                new ListQueryDTO(1, 20, null, [], null, null),
                new ResolvedListFilters(null, [], null, null)
            );
            self::fail('an unknown Host language ID must not be listed');
        } catch (EntityNotFoundException) {
            self::assertTrue(true);
        }

        // Delete flow.
        $this->writer->deleteTranslation($resolver->resolveCode($zt), $a);
        self::assertNull($this->reader->getValue('zt', 'ct', 'home', 'a'));
        self::assertSame('zu-value', $this->reader->getValue('zu', 'ct', 'home', 'a'));

        $this->expectException(LanguageNotFoundException::class);
        $resolver->resolveCode(987654);
    }

    public function testKeysSummaryMissingIsComputedAgainstHostLanguagesByCode(): void
    {
        $zt = $this->createLanguage('zt');
        $this->createLanguage('zv');

        $a = $this->createKey('ct', 'home', 'a');
        $this->createKey('ct', 'home', 'b');
        $this->upsert('zt', $a, 'x');
        // A NULL-scope translation never counts as a translation of a Host language.
        $this->upsert(null, $a, 'neutral');

        $reader = new PackageI18nTranslationsReader($this->management, $this->languageLookup);
        $result = $reader->query(
            'ct',
            'home',
            new ListQueryDTO(1, 20, null, ['language_id' => (string) $zt], null, null),
            new ResolvedListFilters(null, ['language_id' => (string) $zt], null, null)
        );

        $missing = [];
        foreach ($result->data as $item) {
            $missing[$item->keyPart] = [$item->totalLanguages, $item->missingCount];
        }
        self::assertSame(['a' => [1, 0], 'b' => [1, 1]], $missing);
    }

    public function testDashboardComposerListsEveryHostLanguageEvenWithoutTranslations(): void
    {
        $this->createLanguage('zt', 'ZT Lang');
        $this->createLanguage('zv', 'ZV Lang');

        $a = $this->createKey('ct', 'home', 'a');
        $b = $this->createKey('ct', 'home', 'b');
        $this->upsert('zt', $a, 'x');
        $this->upsert('zt', $b, 'x');

        $composer = new I18nDashboardLanguageStatsComposer(
            $this->operational,
            $this->languageRepository
        );

        $coverage = [];
        foreach ($composer->coveragePercentByLanguage() as $row) {
            $coverage[$row->label] = $row->count;
        }
        $missing = [];
        foreach ($composer->missingTranslationsByLanguage() as $row) {
            $missing[$row->label] = $row->count;
        }

        self::assertSame(100, $coverage['ZT Lang']);
        self::assertSame(0, $coverage['ZV Lang']);
        self::assertSame(0, $missing['ZT Lang']);
        self::assertSame(2, $missing['ZV Lang']);
    }

    // ── language-code rename ───────────────────────────────────────────────

    public function testLanguageCodeRenameMovesLanguageCoreAndI18nTogetherWithoutOrphans(): void
    {
        $zt = $this->createLanguage('zt');
        $service = $this->changeService();

        $a = $this->createKey('ct', 'home', 'a');
        $b = $this->createKey('ct', 'home', 'b');
        $this->upsert('zt', $a, 'A');
        $this->upsert('zt', $b, 'B');

        $service->changeCode($zt, 'zt2');

        $language = $this->languageRepository->getById($zt);
        self::assertNotNull($language);
        self::assertSame('zt2', $language->code);

        self::assertNull($this->reader->getValue('zt', 'ct', 'home', 'a'));
        self::assertSame('A', $this->reader->getValue('zt2', 'ct', 'home', 'a'));
        self::assertSame('B', $this->reader->getValue('zt2', 'ct', 'home', 'b'));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_translations WHERE language_code = 'zt'"));
        self::assertNull($this->summary->getRow('ct', 'home', 'zt'));
        self::assertSame(['total_keys' => 2, 'translated_count' => 2, 'missing_count' => 0], $this->summary->getRow('ct', 'home', 'zt2'));

        // No I18n row is left under a code that no Host language owns.
        self::assertSame(
            0,
            $this->scalarInt('SELECT COUNT(*) FROM maa_i18n_translations t WHERE t.language_code IS NOT NULL AND NOT EXISTS (SELECT 1 FROM languages l WHERE l.code COLLATE utf8mb4_bin = t.language_code)')
        );
    }

    public function testLanguageCodeRenameIsAtomicWhenI18nRefusesTheTargetCode(): void
    {
        $zu = $this->createLanguage('zu');
        $service = $this->changeService();

        $a = $this->createKey('ct', 'home', 'a');
        $this->upsert('zu', $a, 'ZU');
        // Orphan I18n rows already sitting under the target code (no Host language owns 'orph').
        $this->upsert('orph', $a, 'ORPH');

        try {
            $service->changeCode($zu, 'orph');
            self::fail('Expected LanguageCodeAlreadyInUseException');
        } catch (LanguageCodeAlreadyInUseException) {
            self::assertTrue(true);
        }

        // LanguageCore rolled back together with I18n: nothing half-renamed.
        $language = $this->languageRepository->getById($zu);
        self::assertNotNull($language);
        self::assertSame('zu', $language->code);
        self::assertSame('ZU', $this->reader->getValue('zu', 'ct', 'home', 'a'));
        self::assertSame('ORPH', $this->reader->getValue('orph', 'ct', 'home', 'a'));
        self::assertFalse($this->pdo->inTransaction());
    }

    public function testLanguageCodeRenameNeverSilentlyTrimsTheIdentity(): void
    {
        $zt = $this->createLanguage('zt');
        $service = $this->changeService();

        $a = $this->createKey('ct', 'home', 'a');
        $this->upsert('zt', $a, 'A');

        foreach ([' zt2 ', 'zt2 ', ' zt2', "zt2\n"] as $padded) {
            try {
                $service->changeCode($zt, $padded);
                self::fail('Expected rejection of a code with surrounding whitespace');
            } catch (LanguageUpdateFailedException) {
                self::assertTrue(true);
            }

            // Also directly on the LanguageCore service: rejected, not trimmed-and-stored.
            try {
                $this->languageManagement->updateLanguageCode($zt, $padded);
                self::fail('Expected LanguageManagementService to reject a padded code');
            } catch (LanguageUpdateFailedException) {
                self::assertTrue(true);
            }
        }

        // Nothing was altered: neither the Host code nor any I18n identity, and no trimmed twin exists.
        $language = $this->languageRepository->getById($zt);
        self::assertNotNull($language);
        self::assertSame('zt', $language->code);
        self::assertSame('A', $this->reader->getValue('zt', 'ct', 'home', 'a'));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_translations WHERE language_code IN ('zt2', ' zt2 ')"));

        // The exact, valid value is persisted unchanged.
        $service->changeCode($zt, 'zt2');
        self::assertSame('A', $this->reader->getValue('zt2', 'ct', 'home', 'a'));
    }

    public function testLanguageCodeRenameRejectsCodesOutsideTheStorageContractBeforeTouchingAnything(): void
    {
        $zt = $this->createLanguage('zt');
        $service = $this->changeService();

        $a = $this->createKey('ct', 'home', 'a');
        $this->upsert('zt', $a, 'A');

        try {
            $service->changeCode($zt, str_repeat('z', 17));
            self::fail('Expected InvalidLanguageCodeException');
        } catch (InvalidLanguageCodeException) {
            self::assertTrue(true);
        }

        $language = $this->languageRepository->getById($zt);
        self::assertNotNull($language);
        self::assertSame('zt', $language->code);
        self::assertSame('A', $this->reader->getValue('zt', 'ct', 'home', 'a'));
    }

    public function testConcurrentRenamesAreSerializedAndNeverActOnAStaleOldCode(): void
    {
        if (!function_exists('pcntl_fork') || !function_exists('posix_kill')) {
            self::markTestSkipped('pcntl/posix are required for the concurrent rename proof.');
        }

        $zt = $this->createLanguage('zt');
        $a = $this->createKey('ct', 'home', 'a');
        $this->upsert('zt', $a, 'A');

        // Parent: first rename zt -> zx, still UNCOMMITTED (holds the language row lock).
        $this->pdo->beginTransaction();
        $this->changeService()->changeCode($zt, 'zx');

        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        self::assertIsArray($sockets);
        $pid = pcntl_fork();
        self::assertGreaterThanOrEqual(0, $pid);

        if ($pid === 0) {
            // Child: a second, concurrent rename of the same language (zt -> zt2) on its OWN connection.
            fclose($sockets[0]);
            try {
                $childPdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('ADMIN_DB_HOST'), getenv('ADMIN_DB_NAME')),
                    (string) getenv('ADMIN_DB_USER'),
                    getenv('ADMIN_DB_PASS') ?: null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                fwrite($sockets[1], 'attempting');
                $this->changeServiceFor($childPdo)->changeCode($zt, 'zt2');
                fwrite($sockets[1], 'done');
            } catch (\Throwable $e) {
                fwrite($sockets[1], 'error:' . $e->getMessage());
            }
            // SIGKILL, not exit(): a normal exit would run destructors that close the
            // connection inherited from the parent and break it for the next tests.
            posix_kill(posix_getpid(), SIGKILL);
            exit(1);
        }

        fclose($sockets[1]);
        self::assertSame('attempting', fread($sockets[0], 10));

        $read = [$sockets[0]];
        $write = null;
        $except = null;
        self::assertSame(
            0,
            stream_select($read, $write, $except, 0, 500_000),
            'the concurrent rename must block on the language row lock until the first rename commits'
        );

        $this->pdo->commit();

        $result = stream_get_contents($sockets[0]);
        self::assertIsString($result);
        pcntl_waitpid($pid, $status);
        self::assertSame('done', $result);

        // Fresh connection: the parent's snapshot predates the child's commit.
        // (a new PDO, not MySQLTestHelper::reset(): re-bootstrapping would recreate the tables.)
        $this->pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('ADMIN_DB_HOST'), getenv('ADMIN_DB_NAME')),
            (string) getenv('ADMIN_DB_USER'),
            getenv('ADMIN_DB_PASS') ?: null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        // The second rename saw the CURRENT code (zx), not the stale one (zt):
        // Host and I18n agree on zt2 and nothing is left under zt / zx.
        $language = (new MysqlLanguageRepository($this->pdo))->getById($zt);
        self::assertNotNull($language);
        self::assertSame('zt2', $language->code);
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_translations WHERE language_code = 'zt2'"));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_translations WHERE language_code IN ('zt', 'zx')"));
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function changeService(): PdoLanguageCodeChange
    {
        return new PdoLanguageCodeChange(
            $this->pdo,
            $this->languageRepository,
            $this->languageManagement,
            $this->writer
        );
    }

    /**
     * The same production wiring on another connection (concurrency proof).
     */
    private function changeServiceFor(PDO $pdo): PdoLanguageCodeChange
    {
        $languages = new MysqlLanguageRepository($pdo);
        $keys = new MysqlTranslationKeyRepository($pdo);
        $writer = new TranslationWriteService(
            new PdoTransactionRunner($pdo),
            $keys,
            new MysqlTranslationRepository($pdo, new SystemClock(new DateTimeZone('UTC'))),
            new I18nGovernancePolicyService(
                new MysqlScopeRepository($pdo),
                new MysqlDomainRepository($pdo),
                new MysqlDomainScopeRepository($pdo)
            ),
            new MissingCounterService(
                new MysqlDomainLanguageSummaryRepository($pdo),
                $keys,
                new MysqlKeyStatsRepository($pdo)
            )
        );

        return new PdoLanguageCodeChange(
            $pdo,
            $languages,
            new LanguageManagementService($languages, new MysqlLanguageSettingsRepository($pdo)),
            $writer
        );
    }

    private function createKey(string $scope, string $domain, string $key): int
    {
        return $this->writer->createKey(new CreateKeyCommand($scope, $domain, $key));
    }

    private function upsert(?string $languageCode, int $keyId, string $value): int
    {
        return $this->writer->upsertTranslation(new UpsertTranslationCommand($languageCode, $keyId, $value, null));
    }

    private function createLanguage(string $code, ?string $name = null): int
    {
        return $this->languageManagement->createLanguage(
            $name ?? strtoupper($code) . ' language',
            $code,
            TextDirectionEnum::LTR,
            null
        );
    }

    private function cleanupLanguages(): void
    {
        $in = "'" . implode("','", self::CODES) . "'";
        $this->pdo->exec("DELETE FROM language_settings WHERE language_id IN (SELECT id FROM languages WHERE code IN ({$in}))");
        $this->pdo->exec("DELETE FROM languages WHERE code IN ({$in})");
    }

    private function scalarInt(string $sql): int
    {
        $stmt = $this->pdo->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }
}
