<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api;

use App\Bootstrap\Container;
use App\Context\RequestContext;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Repository\AdminEmailRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\NestedTransactionPDO;

class AdminCreateControllerTest extends TestCase
{
    private App $app;
    private NestedTransactionPDO $pdo;
    private StepUpService $stepUpService;
    private AdminEmailRepository $emailRepo;
    private PDOFactory $pdoFactory;

    // Track artifacts for cleanup
    private array $createdActivityLogIds = [];

    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'testing';

        $container = Container::create();

        $config = $container->get(AdminConfigDTO::class);
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config->dbHost, $config->dbName);

        $this->pdo = new NestedTransactionPDO($dsn, $config->dbUser, $_ENV['DB_PASS'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $container->set(PDO::class, $this->pdo);

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        $this->app->addBodyParsingMiddleware();
        $this->app->add(\App\Http\Middleware\RecoveryStateMiddleware::class);
        $this->app->add(\App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware::class);
        $this->app->add(\App\Http\Middleware\RequestContextMiddleware::class);
        $this->app->add(\App\Http\Middleware\RequestIdMiddleware::class);
        $this->app->add(\App\Http\Middleware\HttpRequestTelemetryMiddleware::class);

        $routes = require __DIR__ . '/../../../../routes/web.php';
        $routes($this->app);

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

        $this->stepUpService = $container->get(StepUpService::class);
        $this->emailRepo = $container->get(AdminEmailRepository::class);
        $this->pdoFactory = $container->get(PDOFactory::class);

        $this->pdo->beginTransaction();
        $this->seedRbac();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->rollBack();
        }

        // Cleanup Activity Logs (which are on a separate connection and persisted)
        if (!empty($this->createdActivityLogIds)) {
            $logPdo = $this->pdoFactory->create();
            $ids = implode(',', array_map('intval', $this->createdActivityLogIds));
            $logPdo->exec("DELETE FROM activity_logs WHERE id IN ($ids)");
        }
    }

    private function seedRbac(): void
    {
        $this->pdo->exec("INSERT INTO permissions (name) VALUES ('admin.create')");
        $permId = $this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO roles (name) VALUES ('SuperAdmin')");
        $roleId = $this->pdo->lastInsertId();

        $this->pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)")
            ->execute([$roleId, $permId]);
    }

    private function createActor(array $scopes = [Scope::LOGIN]): string
    {
        $this->pdo->exec("INSERT INTO admins (created_at) VALUES (NOW())");
        $adminId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->query("SELECT id FROM roles WHERE name = 'SuperAdmin'");
        $roleId = $stmt->fetchColumn();
        $this->pdo->prepare("INSERT INTO admin_roles (admin_id, role_id) VALUES (?, ?)")
            ->execute([$adminId, $roleId]);

        $token = bin2hex(random_bytes(32));
        $sessionId = hash('sha256', $token);
        $expiresAt = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');

        $this->pdo->prepare("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES (?, ?, ?)")
            ->execute([$sessionId, $adminId, $expiresAt]);

        $context = new RequestContext(
            requestId: 'setup-' . bin2hex(random_bytes(4)),
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit'
        );

        $this->stepUpService->issuePrimaryGrant($adminId, $token, $context);

        foreach ($scopes as $scope) {
            if ($scope !== Scope::LOGIN) {
                $this->stepUpService->issueScopedGrant($adminId, $token, $scope, $context);
            }
        }

        return $token;
    }

    private function createAdminWithEmail(string $email): void
    {
        $this->pdo->exec("INSERT INTO admins (created_at) VALUES (NOW())");
        $adminId = (int)$this->pdo->lastInsertId();

        /** @var \App\Application\Crypto\AdminIdentifierCryptoServiceInterface $crypto */
        $crypto = $this->app->getContainer()->get(\App\Application\Crypto\AdminIdentifierCryptoServiceInterface::class);

        $blindIndex = $crypto->deriveEmailBlindIndex($email);
        $encrypted = $crypto->encryptEmail($email);

        $this->emailRepo->addEmail($adminId, $blindIndex, $encrypted);
    }

    public function test_create_admin_success(): void
    {
        $token = $this->createActor([Scope::LOGIN, Scope::SECURITY]);
        $email = 'success-' . bin2hex(random_bytes(4)) . '@example.com';

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

        // Verify Admin (Transactional)
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch(), 'Admin not found in transaction');

        // Verify Audit Outbox (Transactional)
        $stmt = $this->pdo->prepare("SELECT * FROM audit_outbox WHERE target_id = ? AND action = 'admin_created'");
        $stmt->execute([$newAdminId]);
        $this->assertNotFalse($stmt->fetch(), 'Audit outbox missing in transaction');

        // Verify Activity Log (Separate Connection)
        $logPdo = $this->pdoFactory->create();
        $stmt = $logPdo->prepare("SELECT * FROM activity_logs WHERE entity_id = ? AND action = 'admin.management.create'");
        $stmt->execute([$newAdminId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($log, 'Activity log missing (via separate connection)');
        if ($log) {
            $this->createdActivityLogIds[] = $log['id'];
        }
    }

    public function test_create_admin_fails_without_step_up(): void
    {
        $token = $this->createActor([Scope::LOGIN]);
        $email = 'failstepup@example.com';

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

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM admins");
        $countAfter = $stmt->fetchColumn();
        $this->assertSame($countBefore, $countAfter);
    }

    public function test_create_admin_fails_if_email_exists(): void
    {
        $token = $this->createActor([Scope::LOGIN, Scope::SECURITY]);
        $email = 'duplicate@example.com';

        $this->createAdminWithEmail($email);

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

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Email already registered', $body['message'] ?? '');
    }

    public function test_create_admin_fails_invalid_email(): void
    {
        $token = $this->createActor([Scope::LOGIN, Scope::SECURITY]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit'
            ])
            ->withCookieParams(['auth_token' => $token])
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write(json_encode(['email' => 'invalid-email']));
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }
}
