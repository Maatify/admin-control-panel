<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use DateTimeZone;
use DI\ContainerBuilder;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use Maatify\I18n\Adapter\PhpDi\I18nBindings;
use Maatify\LanguageCore\Bootstrap\LanguageCoreBindings;
use Maatify\LanguageCore\Enum\TextDirectionEnum;
use Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageRepository;
use Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageSettingsRepository;
use Maatify\LanguageCore\Service\LanguageManagementService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\UnifiedEndpointBase;

/**
 * GA-F04 end to end through the REAL Admin HTTP stack (auth, permissions, DI,
 * controllers, error mapping) on a real database: the Admin I18n endpoints are
 * served by the I18n package's public API, the Admin response contract is
 * unchanged, the Host composes its own language metadata with exact-code facts,
 * and Admin error codes stay stable (the package's semantic exceptions are
 * translated by the Host services).
 */
final class I18nAdminApiPackageOwnershipIntegrationTest extends UnifiedEndpointBase
{
    private const PERMISSIONS = [
        'i18n.scopes.list', 'i18n.scopes.create', 'i18n.scopes.change_code', 'i18n.scopes.set_active',
        'i18n.scopes.update_sort', 'i18n.scopes.update_metadata', 'i18n.scopes.details',
        'i18n.scopes.domains.assign', 'i18n.scopes.domains.unassign', 'i18n.scopes.domains.keys',
        'i18n.scopes.domains.translations', 'i18n.scopes.coverage.domain', 'i18n.scopes.keys',
        'i18n.scopes.keys.create', 'i18n.scopes.keys.update_name', 'i18n.scopes.keys.update_metadata',
        'i18n.scopes.dropdown', 'i18n.domains.list', 'i18n.domains.create', 'i18n.domains.change_code',
        'i18n.domains.set_active', 'i18n.domains.update_sort', 'i18n.domains.update_metadata',
        'i18n.scopes.domains.dropdown',
    ];

    private const TOKEN = 'i18n-admin-token';

    /** @var list<string> */
    private array $languageCodes = [];

    protected function configureContainer($containerBuilder): void
    {
        /** @var ContainerBuilder<\DI\Container> $containerBuilder */
        LanguageCoreBindings::register($containerBuilder);
        I18nBindings::register($containerBuilder);

        // Host media config the Twig-based UI controllers need (unrelated to I18n).
        $containerBuilder->addDefinitions([
            MediaUrlConfigDTO::class => static fn (): MediaUrlConfigDTO => new MediaUrlConfigDTO(
                assetsCdnUrl: 'https://cdn.example.test/assets',
                cdnImageUrl: 'https://cdn.example.test/images',
                assetVersion: null,
            ),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = $this->pdo();
        $schema = file_get_contents(dirname(__DIR__, 4) . '/vendor/maatify/php-i18n/schema/schema.i18n.sql');
        self::assertIsString($schema);
        $pdo->exec($schema);

        $this->seedAdmin($pdo);
    }

    protected function tearDown(): void
    {
        $pdo = $this->pdo();
        if ($this->languageCodes !== []) {
            $in = "'" . implode("','", $this->languageCodes) . "'";
            $pdo->exec("DELETE FROM language_settings WHERE language_id IN (SELECT id FROM languages WHERE code IN ({$in}))");
            $pdo->exec("DELETE FROM languages WHERE code IN ({$in})");
        }

        parent::tearDown();
    }

    public function testEveryI18nControllerAndTheDashboardI18nCollaboratorsResolveFromTheRealContainer(): void
    {
        $container = $this->app->getContainer();
        self::assertNotNull($container);

        // Dashboard I18n collaborators (the controller itself needs unrelated Host media config).
        $classes = [
            \Maatify\AdminKernel\Domain\I18n\Dashboard\I18nDashboardLanguageStatsComposer::class,
            \Maatify\I18n\Management\Service\I18nOperationalReadService::class,
        ];
        $base = dirname(__DIR__, 4) . '/Modules/AdminKernel/Http/Controllers';
        foreach (['Api/I18n', 'Ui/I18n'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base . '/' . $dir, \FilesystemIterator::SKIP_DOTS));
            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $relative = substr($file->getPathname(), strlen($base) + 1, -4);
                    $classes[] = 'Maatify\\AdminKernel\\Http\\Controllers\\' . str_replace('/', '\\', $relative);
                }
            }
        }

        self::assertGreaterThan(40, count($classes));
        foreach ($classes as $class) {
            self::assertInstanceOf($class, $container->get($class), $class);
        }
    }

    // ── scopes / domains: Admin contract + package-owned ordering, locking, errors ──

    public function testScopeAndDomainLifecycleThroughTheAdminApi(): void
    {
        $a = $this->post('/api/i18n/scopes/create', ['code' => 'sa', 'name' => 'Scope A', 'description' => 'first']);
        self::assertSame(200, $a->getStatusCode(), (string) $a->getBody());
        $b = $this->post('/api/i18n/scopes/create', ['code' => 'sb', 'name' => 'Scope B']);
        $idA = $this->json($a)['id'];
        $idB = $this->json($b)['id'];
        self::assertIsInt($idA);
        self::assertIsInt($idB);

        // package-appended, contiguous display order
        $list = $this->json($this->post('/api/i18n/scopes/query', []));
        $rows = $list['data'];
        self::assertIsArray($rows);
        self::assertSame(['sa', 'sb'], array_column($rows, 'code'));
        self::assertSame([1, 2], array_column($rows, 'sort_order'));
        self::assertSame('first', $rows[0]['description']);
        self::assertSame(['page', 'per_page', 'total', 'filtered'], array_keys($list['pagination']));
        self::assertSame(2, $list['pagination']['filtered']);

        // duplicate code -> the Admin conflict contract
        $dup = $this->post('/api/i18n/scopes/create', ['code' => 'sa', 'name' => 'again']);
        self::assertSame(409, $dup->getStatusCode(), (string) $dup->getBody());
        self::assertFalse($this->json($dup)['success']);

        // move B to the first position (persistence ordering)
        self::assertSame(200, $this->post('/api/i18n/scopes/update-sort', ['id' => $idB, 'position' => 1])->getStatusCode());
        $order = $this->json($this->post('/api/i18n/scopes/query', []))['data'];
        self::assertSame(['sb', 'sa'], array_column($order, 'code'));

        // missing row -> Admin not-found
        self::assertSame(404, $this->post('/api/i18n/scopes/set-active', ['id' => 999999, 'is_active' => false])->getStatusCode());
        self::assertSame(200, $this->post('/api/i18n/scopes/set-active', ['id' => $idA, 'is_active' => false])->getStatusCode());
        self::assertSame(0, $this->scalarInt("SELECT is_active FROM maa_i18n_scopes WHERE code = 'sa'"));

        // metadata
        self::assertSame(200, $this->post('/api/i18n/scopes/update-metadata', ['id' => $idA, 'name' => 'Renamed'])->getStatusCode());
        self::assertSame('Renamed', $this->scalarString("SELECT name FROM maa_i18n_scopes WHERE code = 'sa'"));

        // unused code can change; the Package decides
        self::assertSame(200, $this->post('/api/i18n/scopes/change-code', ['id' => $idA, 'new_code' => 'sa2'])->getStatusCode());
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_scopes WHERE code = 'sa2'"));

        // domains
        $d = $this->post('/api/i18n/domains/create', ['code' => 'dm', 'name' => 'Domain']);
        self::assertSame(200, $d->getStatusCode(), (string) $d->getBody());
        $domains = $this->json($this->post('/api/i18n/domains/query', []))['data'];
        self::assertSame(['dm'], array_column($domains, 'code'));
    }

    public function testAssignmentKeysAndTheInUseGuardAreOwnedByThePackage(): void
    {
        $scopeId = $this->json($this->post('/api/i18n/scopes/create', ['code' => 'sc', 'name' => 'S']))['id'];
        $this->post('/api/i18n/domains/create', ['code' => 'dc', 'name' => 'D']);
        self::assertIsInt($scopeId);

        // assign / duplicate assign / unknown domain
        self::assertSame(200, $this->post("/api/i18n/scopes/{$scopeId}/domains/assign", ['domain_code' => 'dc'])->getStatusCode());
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_domain_scopes WHERE scope_code = 'sc' AND domain_code = 'dc'"));
        $again = $this->post("/api/i18n/scopes/{$scopeId}/domains/assign", ['domain_code' => 'dc']);
        self::assertSame(true, in_array($again->getStatusCode(), [400, 409, 422], true), (string) $again->getBody());
        self::assertSame(404, $this->post("/api/i18n/scopes/{$scopeId}/domains/assign", ['domain_code' => 'nope'])->getStatusCode());

        $assigned = $this->json($this->post("/api/i18n/scopes/{$scopeId}/domains/query", ['search' => ['columns' => ['assigned' => '1']]]));
        self::assertSame(['dc'], array_column($assigned['data'], 'code'));
        self::assertSame(1, $assigned['data'][0]['assigned']);

        // the scope code is now in use -> Admin in-use conflict, nothing changed
        $inUse = $this->post('/api/i18n/scopes/change-code', ['id' => $scopeId, 'new_code' => 'sc2']);
        self::assertSame(409, $inUse->getStatusCode(), (string) $inUse->getBody());
        self::assertSame('ENTITY_IN_USE', $this->json($inUse)['error']['code']);
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_scopes WHERE code = 'sc'"));

        // keys through the Package; list is scoped + paginated
        $created = $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => 'dc', 'key_name' => 'title', 'description' => 'd']);
        self::assertSame(200, $created->getStatusCode(), (string) $created->getBody());
        $dupKey = $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => 'dc', 'key_name' => 'title']);
        self::assertSame(409, $dupKey->getStatusCode());

        $keys = $this->json($this->post("/api/i18n/scopes/{$scopeId}/keys/query", []));
        self::assertSame(['title'], array_column($keys['data'], 'key_part'));
        // `total` is the GLOBAL key population, `filtered` the scope-bound one
        $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => 'dc', 'key_name' => 'other']);
        $this->pdo()->exec("INSERT INTO maa_i18n_keys (scope, domain, key_part) VALUES ('elsewhere', 'dc', 'x')");
        $keys = $this->json($this->post("/api/i18n/scopes/{$scopeId}/keys/query", []));
        self::assertSame([3, 2], [$keys['pagination']['total'], $keys['pagination']['filtered']]);
        $keys = $this->json($this->post("/api/i18n/scopes/{$scopeId}/keys/query", ['search' => ['columns' => ['key_part' => 'title']]]));
        self::assertSame([3, 1], [$keys['pagination']['total'], $keys['pagination']['filtered']]);
        $keyId = $keys['data'][0]['id'];

        self::assertSame(200, $this->post("/api/i18n/scopes/{$scopeId}/keys/update-name", ['key_id' => $keyId, 'key_name' => 'heading'])->getStatusCode());
        self::assertSame('heading', $this->scalarString("SELECT key_part FROM maa_i18n_keys WHERE id = {$keyId}"));
        self::assertSame(200, $this->post("/api/i18n/scopes/{$scopeId}/keys/update_metadata", ['key_id' => $keyId, 'description' => 'new'])->getStatusCode());
        self::assertSame(404, $this->post("/api/i18n/scopes/{$scopeId}/keys/update-name", ['key_id' => 999999, 'key_name' => 'x'])->getStatusCode());
    }

    // ── Host composition of language metadata with exact-code package facts ──

    public function testCoverageAndTranslationGridsComposeHostLanguagesWithPackageFacts(): void
    {
        $languageIds = $this->createLanguages(['zt', 'zu']);

        $scopeId = $this->json($this->post('/api/i18n/scopes/create', ['code' => 'sd', 'name' => 'S']))['id'];
        $domainId = $this->json($this->post('/api/i18n/domains/create', ['code' => 'dd', 'name' => 'D']))['id'];
        self::assertIsInt($scopeId);
        $this->post("/api/i18n/scopes/{$scopeId}/domains/assign", ['domain_code' => 'dd']);
        $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => 'dd', 'key_name' => 'a']);
        $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => 'dd', 'key_name' => 'b']);
        $this->pdo()->exec("INSERT INTO maa_i18n_translations (key_id, language_code, value) SELECT id, 'zt', 'x' FROM maa_i18n_keys WHERE key_part = 'a'");
        $this->pdo()->exec('DELETE FROM maa_i18n_domain_language_summary');
        $this->pdo()->exec("INSERT INTO maa_i18n_domain_language_summary (scope, domain, language_code, total_keys, translated_count, missing_count) VALUES ('sd', 'dd', 'zt', 2, 1, 1)");

        $coverage = $this->json($this->get("/api/i18n/scopes/{$scopeId}/coverage"));
        $byCode = [];
        foreach ($coverage['data'] ?? $coverage as $row) {
            if (is_array($row) && isset($row['language_code'])) {
                $byCode[$row['language_code']] = $row;
            }
        }
        self::assertArrayHasKey('zt', $byCode);
        self::assertArrayHasKey('zu', $byCode, 'a Host language without I18n rows is still listed (translated = 0)');
        self::assertSame([$languageIds['zt'], 2, 1, 1], [$byCode['zt']['language_id'], $byCode['zt']['total_keys'], $byCode['zt']['translated_count'], $byCode['zt']['missing_count']]);
        self::assertSame([2, 0, 2], [$byCode['zu']['total_keys'], $byCode['zu']['translated_count'], $byCode['zu']['missing_count']]);

        $grid = $this->json($this->post("/api/i18n/scopes/{$scopeId}/domains/{$domainId}/translations/query", []));
        // one row per (key x Host language): the Host registry may already hold other languages
        self::assertSame(2 * $this->scalarInt('SELECT COUNT(*) FROM languages'), $grid['pagination']['total']);
        $cells = [];
        foreach ($grid['data'] as $row) {
            $cells[] = [$row['key_part'], $row['language_code'], $row['value'], $row['language_id'] === ($languageIds[$row['language_code']] ?? $row['language_id'])];
        }
        self::assertContains(['a', 'zt', 'x', true], $cells);
        self::assertContains(['a', 'zu', null, true], $cells);

        $summary = $this->json($this->post("/api/i18n/scopes/{$scopeId}/domains/{$domainId}/keys/query", ['search' => ['columns' => ['missing' => '1']]]));
        $missing = [];
        foreach ($summary['data'] as $row) {
            $missing[$row['key_part']] = [$row['total_languages'], $row['missing_count']];
        }
        // key `a` has a `zt` translation only; key `b` has none (every Host language counts)
        $hostLanguages = $this->scalarInt('SELECT COUNT(*) FROM languages');
        self::assertSame(['a' => [$hostLanguages, $hostLanguages - 1], 'b' => [$hostLanguages, $hostLanguages]], $missing);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function seedAdmin(PDO $pdo): void
    {
        $pdo->exec("INSERT INTO admins (id, display_name, status) VALUES (1, 'I18n Admin', 'ACTIVE')");
        foreach (self::PERMISSIONS as $i => $name) {
            $id = $i + 1;
            $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES ({$id}, '{$name}', '{$name}')");
            $pdo->exec("INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed) VALUES (1, {$id}, 1)");
        }

        $tokenHash = hash('sha256', self::TOKEN);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('{$tokenHash}', 1, '{$expires}')");

        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');
        $pdo->exec(
            "INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use)
             VALUES (1, '{$tokenHash}', 'login', '{$riskHash}', '{$issued}', '{$expires}', 0)"
        );
    }

    private function pdo(): PDO
    {
        self::assertInstanceOf(PDO::class, $this->pdo);

        return $this->pdo;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function post(string $path, array $body): ResponseInterface
    {
        $request = $this->createRequest('POST', $path, $body)
            ->withCookieParams(['auth_token' => self::TOKEN]);

        return $this->app->handle($request);
    }

    private function get(string $path): ResponseInterface
    {
        return $this->app->handle(
            $this->createRequest('GET', $path)->withCookieParams(['auth_token' => self::TOKEN])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded, (string) $response->getBody());

        // JsonResponseFactory::data() wraps the payload in {success, data}; query
        // endpoints that write the DTO directly return the DTO shape itself.
        if (($decoded['success'] ?? null) === true && is_array($decoded['data'] ?? null)) {
            /** @var array<string, mixed> $inner */
            $inner = $decoded['data'];

            return $inner;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param list<string> $codes
     *
     * @return array<string, int>
     */
    private function createLanguages(array $codes): array
    {
        $languages = new MysqlLanguageRepository($this->pdo());
        $service = new LanguageManagementService($languages, new MysqlLanguageSettingsRepository($this->pdo()));

        $ids = [];
        foreach ($codes as $code) {
            $ids[$code] = $service->createLanguage(strtoupper($code) . ' language', $code, TextDirectionEnum::LTR, null);
            $this->languageCodes[] = $code;
        }

        return $ids;
    }

    private function scalarInt(string $sql): int
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }

    private function scalarString(string $sql): string
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_string($value) ? $value : '';
    }
}
