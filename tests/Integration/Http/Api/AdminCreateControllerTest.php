<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Bootstrap\Container;
use App\Context\RequestContext;
use App\Domain\Enum\Scope;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class AdminCreateControllerTest extends TestCase
{
    private App $app;
    private PDO $pdo;
    private array $createdAdminIds = [];
    private array $createdSessionIds = [];
    private array $createdPermissionIds = [];

    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $container = Container::create();
        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        // Register Global Middleware
        $this->app->addBodyParsingMiddleware();
        $this->app->add(\App\Http\Middleware\RecoveryStateMiddleware::class);
        $this->app->add(\App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware::class);
        $this->app->add(\App\Http\Middleware\RequestContextMiddleware::class);
        $this->app->add(\App\Http\Middleware\RequestIdMiddleware::class);
        $this->app->add(\App\Http\Middleware\HttpRequestTelemetryMiddleware::class);

        // Register Routes
        $routes = require __DIR__ . '/../../../../routes/web.php';
        $routes($this->app);

        // Add Error Middleware
        $errorMiddleware = $this->app->addErrorMiddleware(true, false, false);

        $responseFactory = $this->app->getResponseFactory();

        $httpJsonError = function (int $status, string $code, string $message) use ($responseFactory) {
            $payload = json_encode(['message' => $message, 'code' => $code], JSON_THROW_ON_ERROR);
            $response = $responseFactory->createResponse($status);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        };

        $errorMiddleware->setErrorHandler(\App\Modules\Validation\Exceptions\ValidationFailedException::class,
            function ($request, $exception) use ($responseFactory) {
                $payload = json_encode(['error' => 'Invalid request payload', 'errors' => $exception->getErrors()], JSON_THROW_ON_ERROR);
                $response = $responseFactory->createResponse(422);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json');
            }
        );

        $errorMiddleware->setErrorHandler(\Slim\Exception\HttpBadRequestException::class,
            function ($request, $exception) use ($httpJsonError) {
                return $httpJsonError(400, 'BAD_REQUEST', $exception->getMessage());
            }
        );

        $this->pdo = $container->get(PDO::class);
    }

    protected function tearDown(): void
    {
        // Cleanup created data
        if (!empty($this->createdAdminIds)) {
            $ids = implode(',', array_map('intval', $this->createdAdminIds));

            // Delete from admins (cascades to admin_emails, admin_passwords, etc.)
            $this->pdo->exec("DELETE FROM admins WHERE id IN ($ids)");

            // Delete related logs
            $this->pdo->exec("DELETE FROM activity_logs WHERE actor_id IN ($ids) AND actor_type = 'admin'");
            $this->pdo->exec("DELETE FROM activity_logs WHERE entity_id IN ($ids) AND entity_type = 'admin'");
            $this->pdo->exec("DELETE FROM audit_outbox WHERE actor_id IN ($ids) AND actor_type = 'admin'");
            $this->pdo->exec("DELETE FROM audit_outbox WHERE target_id IN ($ids) AND target_type = 'admin'");
            $this->pdo->exec("DELETE FROM security_events WHERE actor_id IN ($ids) AND actor_type = 'admin'");
        }

        if (!empty($this->createdPermissionIds)) {
             $ids = implode(',', array_map('intval', $this->createdPermissionIds));
             $this->pdo->exec("DELETE FROM permissions WHERE id IN ($ids)");
        }
    }

    private function createAuthenticatedAdminWithScope(array $scopes = ['login']): string
    {
        // 1. Create Admin
        $this->pdo->exec("INSERT INTO admins (created_at) VALUES (NOW())");
        $adminId = (int)$this->pdo->lastInsertId();
        $this->createdAdminIds[] = $adminId;

        // 2. Create Permission if needed (admin.create)
        // We use admin_direct_permissions to avoid Role setup complexity
        $permId = null;
        $stmt = $this->pdo->prepare("SELECT id FROM permissions WHERE name = ?");
        $stmt->execute(['admin.create']);
        $permId = $stmt->fetchColumn();

        if (!$permId) {
            $this->pdo->prepare("INSERT INTO permissions (name) VALUES (?)")->execute(['admin.create']);
            $permId = (int)$this->pdo->lastInsertId();
            $this->createdPermissionIds[] = $permId;
        }

        // Grant permission to admin
        $this->pdo->prepare("INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed) VALUES (?, ?, 1)")
            ->execute([$adminId, $permId]);

        // 3. Create Session
        $token = bin2hex(random_bytes(32));
        $sessionId = hash('sha256', $token);

        $this->pdo->prepare("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES (?, ?, ?)")
            ->execute([
                $sessionId,
                $adminId,
                (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s')
            ]);
        $this->createdSessionIds[] = $sessionId;

        // 4. Create Grants
        // We need 'login' grant for SessionState::ACTIVE
        // We need 'admin.create' grant for Scope check if requested

        // Mocking RequestContext risk hash: hash('sha256', '127.0.0.1|PHPUnit')
        // But we need to match what the test request sends.
        // The Middleware gets IP from ServerRequestInterface.
        // ServerRequestFactory defaults? usually empty or localhost.
        // Let's assume we set IP 127.0.0.1 and UserAgent 'PHPUnit' in the request.
        $riskHash = hash('sha256', '127.0.0.1|PHPUnit');

        foreach ($scopes as $scope) {
            $this->pdo->prepare("INSERT INTO step_up_grants
                (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use)
                VALUES (?, ?, ?, ?, NOW(), ?, 0)")
                ->execute([
                    $adminId,
                    $sessionId,
                    $scope,
                    $riskHash,
                    (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s')
                ]);
        }

        return $token;
    }

    private function createAdminWithEmail(string $email): int
    {
        $this->pdo->exec("INSERT INTO admins (created_at) VALUES (NOW())");
        $adminId = (int)$this->pdo->lastInsertId();
        $this->createdAdminIds[] = $adminId;

        /** @var AdminIdentifierCryptoServiceInterface $crypto */
        $crypto = $this->app->getContainer()->get(AdminIdentifierCryptoServiceInterface::class);
        $blindIndex = $crypto->deriveEmailBlindIndex($email);
        $encrypted = $crypto->encryptEmail($email);

        $this->pdo->prepare("INSERT INTO admin_emails
            (admin_id, email_blind_index, email_ciphertext, email_iv, email_tag, email_key_id)
            VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([
                $adminId,
                $blindIndex,
                $encrypted->ciphertext,
                $encrypted->iv,
                $encrypted->tag,
                $encrypted->keyId
            ]);

        return $adminId;
    }

    public function test_create_admin_success(): void
    {
        $token = $this->createAuthenticatedAdminWithScope(['login', 'security']);

        $email = 'newadmin-' . bin2hex(random_bytes(4)) . '@example.com';

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit'
            ])
            ->withCookieParams(['auth_token' => $token])
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write(json_encode(['email' => $email]));
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('temp_password', $body);
        $this->assertArrayHasKey('admin_id', $body);

        $newAdminId = $body['admin_id'];
        $this->createdAdminIds[] = $newAdminId;

        // Verify DB
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch());

        $stmt = $this->pdo->prepare("SELECT * FROM admin_passwords WHERE admin_id = ? AND must_change_password = 1");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch(), 'Password record missing or must_change_password not set');

        // Verify Activity Log
        $stmt = $this->pdo->prepare("SELECT * FROM activity_logs WHERE entity_id = ? AND action = 'admin.management.create'");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch(), 'Activity log missing');

        // Verify Audit Outbox
        $stmt = $this->pdo->prepare("SELECT * FROM audit_outbox WHERE target_id = ? AND action = 'admin_created'");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch(), 'Audit outbox missing');
    }

    public function test_create_admin_fails_without_step_up(): void
    {
        // Only login scope, missing security
        $token = $this->createAuthenticatedAdminWithScope(['login']);

        $email = 'failstepup@example.com';

        // Count admins before
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM admins");
        $countBefore = $stmt->fetchColumn();

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit'
            ])
            ->withCookieParams(['auth_token' => $token])
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write(json_encode(['email' => $email]));
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertSame(403, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame('STEP_UP_REQUIRED', $body['code'] ?? null);
        $this->assertSame('security', $body['scope'] ?? null);

        // Verify rollback / no side effects
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM admins");
        $countAfter = $stmt->fetchColumn();
        $this->assertSame($countBefore, $countAfter, 'Admin count changed on failure');
    }

    public function test_create_admin_fails_if_email_exists(): void
    {
        $token = $this->createAuthenticatedAdminWithScope(['login', 'security']);

        $existingEmail = 'existing@example.com';
        $this->createAdminWithEmail($existingEmail);

        // Count admins before
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM admins");
        $countBefore = $stmt->fetchColumn();

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit'
            ])
            ->withCookieParams(['auth_token' => $token])
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write(json_encode(['email' => $existingEmail]));
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Email already registered', $body['message'] ?? '');

        // Verify rollback / no side effects
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM admins");
        $countAfter = $stmt->fetchColumn();
        $this->assertSame($countBefore, $countAfter, 'Admin count changed on failure');
    }

    public function test_create_admin_fails_invalid_email(): void
    {
        $token = $this->createAuthenticatedAdminWithScope(['login', 'security']);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit'
            ])
            ->withCookieParams(['auth_token' => $token])
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write(json_encode(['email' => 'not-an-email']));
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }
}
