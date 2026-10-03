<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use DI\ContainerBuilder;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use Maatify\I18n\Adapter\PhpDi\I18nBindings;
use Maatify\LanguageCore\Bootstrap\LanguageCoreBindings;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\UnifiedEndpointBase;

/**
 * Endpoint tests (HTTP layer, full middleware pipeline, real controllers, services,
 * repositories and a real database) for the three language/translation mutations whose
 * behaviour changed with the move to the maatify/php-i18n package:
 *
 *   POST /api/languages/update-code
 *   POST /api/languages/{language_id}/translations/upsert
 *   POST /api/languages/{language_id}/translations/delete
 *
 * Covers success, validation/error mapping, no side effects on rejection, and
 * fail-closed behaviour (an authenticated admin WITHOUT the permission, and an
 * unauthenticated request, must change nothing).
 */
final class I18nAdminLanguageMutationEndpointsIntegrationTest extends UnifiedEndpointBase
{
    private const GRANTED_TOKEN = 'lang-mutation-granted';
    private const UNPRIVILEGED_TOKEN = 'lang-mutation-unprivileged';

    private const PERMISSIONS = [
        'languages.create', 'languages.update.code',
        'languages.translations.upsert', 'languages.translations.delete',
        'i18n.scopes.create', 'i18n.domains.create', 'i18n.scopes.domains.assign', 'i18n.scopes.keys.create',
    ];

    /** Codes the tests rename a language to (cleaned up in tearDown). */
    private const RENAME_TARGETS = ['ztb', 'ztz'];

    /** @var list<string> */
    private array $languageCodes = [];

    protected function configureContainer($containerBuilder): void
    {
        /** @var ContainerBuilder<\DI\Container> $containerBuilder */
        LanguageCoreBindings::register($containerBuilder);
        I18nBindings::register($containerBuilder);

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

        $schema = file_get_contents(dirname(__DIR__, 4) . '/vendor/maatify/php-i18n/schema/schema.i18n.sql');
        self::assertIsString($schema);
        $this->pdo()->exec($schema);

        $this->seedAdmin(1, 'Granted Admin', self::GRANTED_TOKEN, true);
        $this->seedAdmin(2, 'Unprivileged Admin', self::UNPRIVILEGED_TOKEN, false);
    }

    protected function tearDown(): void
    {
        // Every code this class creates OR renames a language to, so no language survives a test.
        $in = "'" . implode("','", array_unique([...$this->languageCodes, ...self::RENAME_TARGETS])) . "'";
        $this->pdo()->exec("DELETE FROM language_settings WHERE language_id IN (SELECT id FROM languages WHERE code IN ({$in}))");
        $this->pdo()->exec("DELETE FROM languages WHERE code IN ({$in})");

        parent::tearDown();
    }

    // ── update-code ─────────────────────────────────────────────────────────

    public function testUpdateCodeRenamesTheLanguageAndReKeysItsTranslationsAtomically(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');
        self::assertSame(200, $this->upsert($langId, $keyId, 'hello')->getStatusCode());

        $response = $this->post('/api/languages/update-code', ['language_id' => $langId, 'code' => 'ztb']);

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame('ztb', $this->languageCode($langId));
        self::assertSame([['ztb', 'hello']], $this->translationRows($keyId));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_translations WHERE language_code = 'zta'"));
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_domain_language_summary WHERE language_code = 'ztb'"));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_domain_language_summary WHERE language_code = 'zta'"));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function surroundingWhitespaceCodes(): array
    {
        return [
            'leading and trailing spaces' => [' ztb '],
            'trailing space' => ['ztb '],
            'leading space' => [' ztb'],
            'trailing newline' => ["ztb\n"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('surroundingWhitespaceCodes')]
    public function testUpdateCodeWithSurroundingWhitespaceIsRejectedAndChangesNothing(string $code): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($langId, $keyId, 'hello');

        $response = $this->post('/api/languages/update-code', ['language_id' => $langId, 'code' => $code]);

        self::assertGreaterThanOrEqual(400, $response->getStatusCode(), (string) $response->getBody());
        $error = $this->decode($response)['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame('LANGUAGE_UPDATE_FAILED', $error['code']);

        self::assertSame('zta', $this->languageCode($langId));
        self::assertSame([['zta', 'hello']], $this->translationRows($keyId));
    }

    public function testUpdateCodeToACodeAlreadyOwnedByAnotherLanguageIsRefusedAndChangesNothing(): void
    {
        $langId = $this->createLanguage('zta');
        $this->createLanguage('ztc');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($langId, $keyId, 'hello');

        $response = $this->post('/api/languages/update-code', ['language_id' => $langId, 'code' => 'ztc']);

        self::assertSame(409, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame('zta', $this->languageCode($langId));
        self::assertSame([['zta', 'hello']], $this->translationRows($keyId));
    }

    public function testUpdateCodeOfAnUnknownLanguageIsNotFound(): void
    {
        $response = $this->post('/api/languages/update-code', ['language_id' => 987654, 'code' => 'ztz']);

        self::assertSame(404, $response->getStatusCode(), (string) $response->getBody());
    }

    // ── translations/upsert ─────────────────────────────────────────────────

    public function testUpsertCreatesThenUpdatesOneExactTranslationAndKeepsTheSummaryExact(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');

        self::assertSame(200, $this->upsert($langId, $keyId, 'first')->getStatusCode());
        self::assertSame([['zta', 'first']], $this->translationRows($keyId));

        self::assertSame(200, $this->upsert($langId, $keyId, 'second')->getStatusCode());
        self::assertSame([['zta', 'second']], $this->translationRows($keyId));
        self::assertSame(
            [[1, 1, 0]],
            $this->rows("SELECT total_keys, translated_count, missing_count FROM maa_i18n_domain_language_summary WHERE language_code = 'zta'", false)
        );
    }

    public function testUpsertRejectsAnEmptyValueAndWritesNothing(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');

        $response = $this->upsert($langId, $keyId, '');

        self::assertSame(422, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame([], $this->translationRows($keyId));
    }

    public function testUpsertForAnUnknownKeyOrAnUnknownLanguageIsNotFoundAndWritesNothing(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');

        $unknownKey = $this->upsert($langId, 99999999, 'x');
        self::assertSame(404, $unknownKey->getStatusCode(), (string) $unknownKey->getBody());
        self::assertSame('TRANSLATION_KEY_NOT_FOUND', ($this->decode($unknownKey)['error']['code'] ?? null));

        $unknownLanguage = $this->upsert(987654, $keyId, 'x');
        self::assertSame(404, $unknownLanguage->getStatusCode(), (string) $unknownLanguage->getBody());

        self::assertSame(0, $this->scalarInt('SELECT COUNT(*) FROM maa_i18n_translations'));
    }

    // ── translations/delete ─────────────────────────────────────────────────

    public function testDeleteRemovesOnlyThatLanguagesTranslationAndUpdatesTheSummary(): void
    {
        $zta = $this->createLanguage('zta');
        $ztc = $this->createLanguage('ztc');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($zta, $keyId, 'A');
        $this->upsert($ztc, $keyId, 'C');

        $response = $this->post("/api/languages/{$zta}/translations/delete", ['key_id' => $keyId]);

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame([['ztc', 'C']], $this->translationRows($keyId));
        // the package keeps summary rows only where a language still has translations in the domain
        self::assertSame(
            [],
            $this->rows("SELECT total_keys, translated_count, missing_count FROM maa_i18n_domain_language_summary WHERE language_code = 'zta'", false)
        );
        self::assertSame(
            [[1, 1, 0]],
            $this->rows("SELECT total_keys, translated_count, missing_count FROM maa_i18n_domain_language_summary WHERE language_code = 'ztc'", false)
        );
    }

    public function testDeleteForAnUnknownLanguageOrAnUnknownKeyIsNotFoundAndChangesNothing(): void
    {
        $zta = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($zta, $keyId, 'A');

        $unknownLanguage = $this->post('/api/languages/987654/translations/delete', ['key_id' => $keyId]);
        self::assertSame(404, $unknownLanguage->getStatusCode(), (string) $unknownLanguage->getBody());

        $unknownKey = $this->post("/api/languages/{$zta}/translations/delete", ['key_id' => 99999999]);
        self::assertSame(404, $unknownKey->getStatusCode(), (string) $unknownKey->getBody());

        self::assertSame([['zta', 'A']], $this->translationRows($keyId));
    }

    // ── fail-closed ─────────────────────────────────────────────────────────

    public function testAnAdminWithoutThePermissionIsDeniedOnAllThreeMutationsAndNothingChanges(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($langId, $keyId, 'hello');

        $attempts = [
            ['/api/languages/update-code', ['language_id' => $langId, 'code' => 'ztb']],
            ["/api/languages/{$langId}/translations/upsert", ['key_id' => $keyId, 'value' => 'hacked']],
            ["/api/languages/{$langId}/translations/delete", ['key_id' => $keyId]],
        ];

        foreach ($attempts as [$path, $body]) {
            $response = $this->post($path, $body, self::UNPRIVILEGED_TOKEN);
            self::assertSame(403, $response->getStatusCode(), $path . ' => ' . (string) $response->getBody());
        }

        self::assertSame('zta', $this->languageCode($langId));
        self::assertSame([['zta', 'hello']], $this->translationRows($keyId));
    }

    public function testAnUnauthenticatedRequestIsRejectedOnAllThreeMutationsAndNothingChanges(): void
    {
        $langId = $this->createLanguage('zta');
        $keyId = $this->createKey('home', 'title');
        $this->upsert($langId, $keyId, 'hello');

        $attempts = [
            ['/api/languages/update-code', ['language_id' => $langId, 'code' => 'ztb']],
            ["/api/languages/{$langId}/translations/upsert", ['key_id' => $keyId, 'value' => 'hacked']],
            ["/api/languages/{$langId}/translations/delete", ['key_id' => $keyId]],
        ];

        foreach ($attempts as [$path, $body]) {
            $response = $this->app->handle($this->createRequest('POST', $path, $body));
            self::assertContains($response->getStatusCode(), [401, 403], $path . ' => ' . (string) $response->getBody());
        }

        self::assertSame('zta', $this->languageCode($langId));
        self::assertSame([['zta', 'hello']], $this->translationRows($keyId));
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function seedAdmin(int $adminId, string $name, string $token, bool $withPermissions): void
    {
        $pdo = $this->pdo();
        $pdo->exec("INSERT INTO admins (id, display_name, status) VALUES ({$adminId}, '{$name}', 'ACTIVE')");

        if ($withPermissions) {
            foreach (self::PERMISSIONS as $i => $permission) {
                $id = $i + 1;
                $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES ({$id}, '{$permission}', '{$permission}')");
                $pdo->exec("INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed) VALUES ({$adminId}, {$id}, 1)");
            }
        }

        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('{$tokenHash}', {$adminId}, '{$expires}')");

        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');
        $pdo->exec(
            "INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use)
             VALUES ({$adminId}, '{$tokenHash}', 'login', '{$riskHash}', '{$issued}', '{$expires}', 0)"
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
    private function post(string $path, array $body, string $token = self::GRANTED_TOKEN): ResponseInterface
    {
        return $this->app->handle(
            $this->createRequest('POST', $path, $body)->withCookieParams(['auth_token' => $token])
        );
    }

    private function createLanguage(string $code): int
    {
        $response = $this->post('/api/languages/create', [
            'name' => strtoupper($code) . ' language',
            'code' => $code,
            'direction' => 'ltr',
            'is_active' => false,
        ]);
        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->languageCodes[] = $code;

        return $this->scalarInt("SELECT id FROM languages WHERE code = '{$code}'");
    }

    /**
     * Governed key `ct.<domain>.<key>`, created through the real Admin API.
     */
    private function createKey(string $domain, string $key): int
    {
        if ($this->scalarInt("SELECT COUNT(*) FROM maa_i18n_scopes WHERE code = 'ct'") === 0) {
            $scope = $this->post('/api/i18n/scopes/create', ['code' => 'ct', 'name' => 'Website']);
            self::assertSame(200, $scope->getStatusCode(), (string) $scope->getBody());
        }
        if ($this->scalarInt("SELECT COUNT(*) FROM maa_i18n_domains WHERE code = '{$domain}'") === 0) {
            $dom = $this->post('/api/i18n/domains/create', ['code' => $domain, 'name' => ucfirst($domain)]);
            self::assertSame(200, $dom->getStatusCode(), (string) $dom->getBody());
            $scopeId = $this->scalarInt("SELECT id FROM maa_i18n_scopes WHERE code = 'ct'");
            $assign = $this->post("/api/i18n/scopes/{$scopeId}/domains/assign", ['domain_code' => $domain]);
            self::assertSame(200, $assign->getStatusCode(), (string) $assign->getBody());
        }

        $scopeId = $this->scalarInt("SELECT id FROM maa_i18n_scopes WHERE code = 'ct'");
        $created = $this->post("/api/i18n/scopes/{$scopeId}/keys/create", ['domain_code' => $domain, 'key_name' => $key]);
        self::assertSame(200, $created->getStatusCode(), (string) $created->getBody());

        return $this->scalarInt("SELECT id FROM maa_i18n_keys WHERE domain = '{$domain}' AND key_part = '{$key}'");
    }

    private function upsert(int $languageId, int $keyId, string $value): ResponseInterface
    {
        return $this->post("/api/languages/{$languageId}/translations/upsert", ['key_id' => $keyId, 'value' => $value]);
    }

    private function languageCode(int $languageId): string
    {
        $stmt = $this->pdo()->query("SELECT code FROM languages WHERE id = {$languageId}");
        self::assertNotFalse($stmt);
        $code = $stmt->fetchColumn();
        self::assertIsString($code);

        return $code;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function translationRows(int $keyId): array
    {
        /** @var list<array{0: string, 1: string}> $rows */
        $rows = $this->rows("SELECT language_code, value FROM maa_i18n_translations WHERE key_id = {$keyId} ORDER BY language_code", false);

        return $rows;
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    private function rows(string $sql, bool $assoc = true): array
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);

        return $stmt->fetchAll($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
    }

    private function scalarInt(string $sql): int
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded, (string) $response->getBody());

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
