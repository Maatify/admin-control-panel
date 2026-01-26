<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Bootstrap\Container;
use App\Context\AdminContext;
use App\Context\RequestContext;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\MySQLTestHelper;
use PDO;
use Psr\Container\ContainerInterface;

class UnifiedEndpointTest extends TestCase
{
    private PDO $pdo;
    /** @var App<ContainerInterface|null> */
    private App $app;

    /** @var array<string, string|false> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // 1. Setup Environment
        $keys = [
            'TEST_FORCE_SQLITE', 'APP_ENV', 'APP_DEBUG', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'APP_TIMEZONE', 'PASSWORD_PEPPERS', 'PASSWORD_ACTIVE_PEPPER_ID', 'PASSWORD_ARGON2_OPTIONS',
            'CRYPTO_KEYS', 'CRYPTO_ACTIVE_KEY_ID', 'EMAIL_BLIND_INDEX_KEY', 'MAIL_HOST', 'MAIL_PORT',
            'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'TOTP_ISSUER',
            'TOTP_ENROLLMENT_TTL_SECONDS', 'RECOVERY_MODE'
        ];

        foreach ($keys as $key) {
            $this->originalEnv[$key] = getenv($key);
        }

        putenv('TEST_FORCE_SQLITE=true');
        $_ENV['TEST_FORCE_SQLITE'] = 'true';

        $envs = [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'true',
            'DB_HOST' => '127.0.0.1',
            'DB_NAME' => 'test_db',
            'DB_USER' => 'root',
            'DB_PASS' => 'secret',
            'APP_TIMEZONE' => 'UTC',
            'PASSWORD_PEPPERS' => '{"1":"00000000000000000000000000000000"}',
            'PASSWORD_ACTIVE_PEPPER_ID' => '1',
            'PASSWORD_ARGON2_OPTIONS' => '{"memory_cost":1024,"time_cost":2,"threads":2}',
            'CRYPTO_KEYS' => '[{"id":"1","key":"' . bin2hex(str_repeat('A', 32)) . '"}]',
            'CRYPTO_ACTIVE_KEY_ID' => '1',
            'EMAIL_BLIND_INDEX_KEY' => bin2hex(str_repeat('B', 32)),
            'MAIL_HOST' => 'localhost',
            'MAIL_PORT' => '1025',
            'MAIL_USERNAME' => 'null',
            'MAIL_PASSWORD' => 'null',
            'MAIL_FROM_ADDRESS' => 'admin@example.com',
            'MAIL_FROM_NAME' => 'Admin',
            'TOTP_ISSUER' => 'AdminPanel',
            'TOTP_ENROLLMENT_TTL_SECONDS' => '300',
            'RECOVERY_MODE' => 'false',
        ];

        foreach ($envs as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }

        // 2. Setup Database
        $this->pdo = MySQLTestHelper::pdo();
        // Truncate relevant tables
        MySQLTestHelper::truncate('admins');
        MySQLTestHelper::truncate('admin_sessions');
        MySQLTestHelper::truncate('admin_passwords');
        MySQLTestHelper::truncate('admin_emails');
        MySQLTestHelper::truncate('admin_roles');

        // 3. Build App with Overridden Container
        $container = Container::create();

        // Override PDO to use our Test Helper PDO (SQLite)
        // We must override both PDO and PDOFactory to be safe, though Container.php binds PDO using PDOFactory
        // Actually, we can just override PDO::class.
        // Wait, `di/php-di` allows setting entries directly.
        // But `Container::create()` returns a built container (ContainerInterface).
        // If it returns a compiled container, we can't easily modify it.
        // `ContainerBuilder::build()` returns a `DI\Container`.

        // Reflection check: `App\Bootstrap\Container::create()` returns `Psr\Container\ContainerInterface`.
        // If it is `DI\Container`, we can use `set()`.

        if ($container instanceof \DI\Container) {
            $container->set(PDO::class, $this->pdo);
        }

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        // Register Middleware & Routes
        $this->app->addBodyParsingMiddleware();
        $routes = require __DIR__ . '/../../../routes/web.php';
        $routes($this->app);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $val) {
            if ($val === false) {
                putenv($key); // Unset
                unset($_ENV[$key]);
            } else {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }

    public function test_revoke_session_updates_database(): void
    {
        // 1. Seed Data
        $adminId = 1;
        $sessionId = 'test_session_123';
        $authToken = 'token_abc'; // In real app, session_id is hash of token, but let's assume direct mapping for simplicity or consistent hashing if feasible.
        // Actually, `AdminSessionRepository` usually hashes the token.
        // Let's verify: `SessionGuardMiddleware` calls `AdminSessionRepository::validateSession($token)`.
        // `validateSession` hashes the token?
        // Let's look at `SessionGuardMiddleware`.

        // For `POST /api/sessions/revoke-bulk`, the input is `session_ids` (array of strings).
        // The controller calls `SessionRevocationService::revokeBulk($sessionIds)`.
        // The service calls `repo->revokeSessions($ids)`.
        // The repo does `UPDATE ... WHERE session_id IN (...)`.

        // So we just need to insert a row with a known `session_id` and call the API with that ID.
        // We need to be authenticated though.

        // Insert Admin
        $this->pdo->exec("INSERT INTO admins (id, display_name, status) VALUES ($adminId, 'Test Admin', 'ACTIVE')");

        // Insert Session (Current Session for Auth)
        $currentSessionId = 'current_session_hash';
        $currentAuthToken = 'current_token_plain';
        // Note: Middleware usually expects `auth_token` cookie.
        // SessionGuardMiddleware does: $token = $cookie['auth_token']; $session = $repo->getByToken($token);
        // If `getByToken` hashes it, we need to insert the HASH in DB.

        // Let's assume standard behavior: DB stores HASH. Middleware has PLAIN.
        // Hash is likely sha256.
        $currentSessionHash = hash('sha256', $currentAuthToken);

        $this->pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at, is_revoked)
                          VALUES ('$currentSessionHash', $adminId, datetime('now', '+1 hour'), 0)");

        // Insert Target Session to Revoke
        $targetSessionId = 'target_session_hash';
        $this->pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at, is_revoked)
                          VALUES ('$targetSessionId', $adminId, datetime('now', '+1 hour'), 0)");

        // Grant Step-Up (login scope) to current session to bypass SessionStateGuard
        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $this->pdo->exec("INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at)
                          VALUES ($adminId, '$currentSessionHash', 'login', '$riskHash', datetime('now'), datetime('now', '+1 hour'))");

        // Grant Permissions (RBAC)
        $this->pdo->exec("INSERT INTO permissions (id, name) VALUES (100, 'sessions.revoke')");
        $this->pdo->exec("INSERT INTO roles (id, name, is_active) VALUES (1, 'super_admin', 1)");
        $this->pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 100)");
        $this->pdo->exec("INSERT INTO admin_roles (admin_id, role_id) VALUES ($adminId, 1)");

        // 2. Prepare Request
        // POST /api/sessions/revoke-bulk
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/sessions/revoke-bulk');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withCookieParams(['auth_token' => $currentAuthToken]);
        $request->getBody()->write((string) json_encode([
            'session_ids' => [$targetSessionId]
        ]));

        // 3. Execute
        $response = $this->app->handle($request);

        // 4. Assert Response
        $this->assertSame(200, $response->getStatusCode(), 'Response should be 200 OK. Body: ' . (string)$response->getBody());

        // 5. Assert DB Side Effect
        $stmt = $this->pdo->prepare("SELECT is_revoked FROM admin_sessions WHERE session_id = ?");
        $stmt->execute([$targetSessionId]);
        $result = $stmt->fetchColumn();

        $this->assertEquals(1, $result, 'Target session should be revoked (is_revoked=1)');

        // Assert Current Session is NOT revoked
        $stmt->execute([$currentSessionHash]);
        $currentResult = $stmt->fetchColumn();
        $this->assertEquals(0, $currentResult, 'Current session should not be revoked');
    }
}
