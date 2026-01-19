<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api;

use App\Bootstrap\Container;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Enum\Scope;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\SessionValidationService;
use App\Domain\Service\StepUpService;
use App\Infrastructure\Database\PDOFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\MySQLTestHelper;

final class AdminCreateControllerTest extends TestCase
{
    private App $app;
    private MockObject|SessionValidationService $sessionValidationServiceMock;
    private MockObject|StepUpService $stepUpServiceMock;
    private MockObject|AuthorizationService $authorizationServiceMock;
    private MockObject|RecoveryStateService $recoveryStateServiceMock;
    private MockObject|AdminPasswordRepositoryInterface $passwordRepositoryMock;
    private MockObject|AuthoritativeSecurityAuditWriterInterface $auditWriterMock;
    private MockObject|PDOFactory $pdoFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Database Setup
        $pdo = MySQLTestHelper::pdo();
        MySQLTestHelper::truncate('admins');
        MySQLTestHelper::truncate('admin_passwords');
        MySQLTestHelper::truncate('audit_outbox');
        MySQLTestHelper::truncate('activity_logs');
        MySQLTestHelper::truncate('admin_emails');

        // 2. Mock Dependencies
        $this->sessionValidationServiceMock = $this->createMock(SessionValidationService::class);
        $this->stepUpServiceMock = $this->createMock(StepUpService::class);
        $this->authorizationServiceMock = $this->createMock(AuthorizationService::class);
        $this->recoveryStateServiceMock = $this->createMock(RecoveryStateService::class);

        // Default behavior: Not locked
        $this->recoveryStateServiceMock->method('isLocked')->willReturn(false);
        // Default behavior: Authorized
        $this->authorizationServiceMock->method('hasPermission')->willReturn(true);

        $this->pdoFactoryMock = $this->createMock(PDOFactory::class);
        $this->pdoFactoryMock->method('create')->willReturn(MySQLTestHelper::pdo());
    }

    private function buildApp(array $overrides = []): void
    {
        $container = Container::create();

        // Always mock Session & StepUp to control Auth/Authz
        $container->set(SessionValidationService::class, $this->sessionValidationServiceMock);
        $container->set(StepUpService::class, $this->stepUpServiceMock);
        $container->set(AuthorizationService::class, $this->authorizationServiceMock);
        $container->set(RecoveryStateService::class, $this->recoveryStateServiceMock);

        // Mock PDO Factory to return shared Test PDO
        $container->set(PDOFactory::class, $this->pdoFactoryMock);

        // Use Test PDO
        $container->set(\PDO::class, MySQLTestHelper::pdo());

        // Apply overrides
        foreach ($overrides as $class => $instance) {
            $container->set($class, $instance);
        }

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        // Add Middleware & Routes (Copied from public/index.php behavior)
        $this->app->addBodyParsingMiddleware();
        $this->app->addErrorMiddleware(true, false, false);

        // Register Routes
        $routes = require __DIR__ . '/../../../../routes/web.php';
        $routes($this->app);
    }

    public function testStepUpRequired(): void
    {
        $this->buildApp();

        // 1. Authenticated but NO Step-Up Grant
        $this->sessionValidationServiceMock->method('validate')->willReturn(1);

        // Expect Check: scope=admin.create
        $this->stepUpServiceMock->method('getSessionState')
            ->willReturn(\App\Domain\Enum\SessionState::ACTIVE);

        $this->stepUpServiceMock->expects($this->once())
            ->method('hasGrant')
            ->with(
                1,
                'valid-session-token',
                $this->callback(fn($scope) => $scope === Scope::SECURITY),
                $this->isInstanceOf(RequestContext::class)
            )
            ->willReturn(false); // Deny

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create')
            ->withCookieParams(['auth_token' => 'valid-session-token'])
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);

        $this->assertSame(403, $response->getStatusCode());

        $body = (string)$response->getBody();
        $json = json_decode($body, true);

        $this->assertSame('STEP_UP_REQUIRED', $json['code'] ?? null);
        $this->assertSame('security', $json['scope'] ?? null);

        // Assert No Side Effects (Fail Closed)
        $pdo = MySQLTestHelper::pdo();
        $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $this->assertEquals(0, $count, 'No admin should be created when step-up is required');

        $count = $pdo->query("SELECT COUNT(*) FROM admin_passwords")->fetchColumn();
        $this->assertEquals(0, $count, 'No password should be created when step-up is required');

        $count = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $this->assertEquals(0, $count, 'No activity log should be created when step-up is required');

        $count = $pdo->query("SELECT COUNT(*) FROM audit_outbox")->fetchColumn();
        $this->assertEquals(0, $count, 'No audit log should be created when step-up is required');
    }

    public function testCreateSuccess(): void
    {
        // Use Real Password Repository (Refactored for SQLite compatibility)
        $this->buildApp();

        // 1. Authenticated & Step-Up Granted
        $this->sessionValidationServiceMock->method('validate')->willReturn(1);
        $this->stepUpServiceMock->method('getSessionState')->willReturn(\App\Domain\Enum\SessionState::ACTIVE);
        $this->stepUpServiceMock->method('hasGrant')->willReturn(true);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create')
            ->withCookieParams(['auth_token' => 'valid-session-token'])
            ->withHeader('Accept', 'application/json');

        // Capture Request ID
        $requestId = 'req-123';
        $request = $request->withAttribute('request_id', $requestId);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->getStatusCode());

        $body = (string)$response->getBody();
        $json = json_decode($body, true);

        // Assert Response Structure
        $this->assertArrayHasKey('admin_id', $json);
        $this->assertArrayHasKey('created_at', $json);
        $this->assertArrayHasKey('temp_password', $json);

        $adminId = $json['admin_id'];
        $tempPassword = $json['temp_password'];

        // Assert Temp Password Format (16 chars hex = 8 bytes)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $tempPassword);

        // Assert NO sensitive leakage
        $this->assertArrayNotHasKey('password_hash', $json);
        $this->assertArrayNotHasKey('pepper_id', $json);
        $this->assertArrayNotHasKey('must_change_password', $json);

        // Assert DB: Admin Created
        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        $this->assertNotFalse($admin, 'Admin record should exist');

        // Assert DB: Password Saved
        $stmt = $pdo->prepare("SELECT * FROM admin_passwords WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $passRecord = $stmt->fetch();
        $this->assertNotFalse($passRecord, 'Password record should exist');
        $this->assertEquals(1, $passRecord['must_change_password']);
        $this->assertNotEmpty($passRecord['password_hash']);
        $this->assertNotEmpty($passRecord['pepper_id']);

        // Assert DB: Activity Log (Correlation ID)
        $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE entity_id = ? AND entity_type = 'admin'");
        $stmt->execute([$adminId]);
        $activity = $stmt->fetch();
        $this->assertNotFalse($activity, 'Activity log should exist');

        $activityMetadata = json_decode($activity['metadata'] ?? '{}', true);
        $this->assertArrayHasKey('correlation_id', $activityMetadata);
        $correlationId = $activityMetadata['correlation_id'];

        // Assert Correlation ID is unique
        $this->assertNotEquals($requestId, $correlationId, 'Correlation ID must not equal Request ID');

        // Assert DB: Audit Outbox
        $stmt = $pdo->prepare("SELECT * FROM audit_outbox WHERE target_id = ? AND target_type = 'admin'");
        $stmt->execute([$adminId]);
        $audit = $stmt->fetch();
        $this->assertNotFalse($audit, 'Audit outbox entry should exist');
        $this->assertEquals('admin_created', $audit['action']);
        $this->assertEquals(1, $audit['actor_id']); // Current Admin ID
        $this->assertEquals($correlationId, $audit['correlation_id'], 'Audit correlation ID matches Activity Log');
    }

    public function testTransactionRollbackOnPasswordFailure(): void
    {
        // Mock Password Repository to throw
        $this->passwordRepositoryMock = $this->createMock(AdminPasswordRepositoryInterface::class);
        $this->passwordRepositoryMock->method('savePassword')->willThrowException(new \RuntimeException('DB Error'));

        // Inject Mock
        $this->buildApp([
            AdminPasswordRepositoryInterface::class => $this->passwordRepositoryMock
        ]);

        $this->sessionValidationServiceMock->method('validate')->willReturn(1);
        $this->stepUpServiceMock->method('getSessionState')->willReturn(\App\Domain\Enum\SessionState::ACTIVE);
        $this->stepUpServiceMock->method('hasGrant')->willReturn(true);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create')
            ->withCookieParams(['auth_token' => 'valid-session-token'])
            ->withHeader('Accept', 'application/json');

        // We expect the exception to propagate or be handled.
        // Slim Error Middleware is enabled, so it might catch it and return 500.
        // Or if we didn't add it, it would throw. I added it in buildApp.

        // However, standard PHPUnit might catch the exception if it bubbles up.
        // Let's wrap in try/catch or expect 500 response.

        try {
            $response = $this->app->handle($request);
            $this->assertSame(500, $response->getStatusCode());
        } catch (\Throwable $e) {
             // Exception thrown is also acceptable proof of failure if not handled by error middleware
             $this->assertTrue(true);
        }

        // Assert Rollback
        $pdo = MySQLTestHelper::pdo();

        // No admin should exist (since ID is auto-inc, we can just check count or last ID,
        // but easier to check if ANY admin was created in this test run.
        // We truncated tables in setUp.
        $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $this->assertEquals(0, $count, 'Admins table should be empty after rollback');

        $count = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $this->assertEquals(0, $count, 'Activity logs should be empty');
    }

    public function testTransactionRollbackOnAuditFailure(): void
    {
        // Mock Audit Writer to throw
        $this->auditWriterMock = $this->createMock(AuthoritativeSecurityAuditWriterInterface::class);
        $this->auditWriterMock->method('write')->willThrowException(new \RuntimeException('Audit Error'));

        $this->buildApp([
            AuthoritativeSecurityAuditWriterInterface::class => $this->auditWriterMock
        ]);

        $this->sessionValidationServiceMock->method('validate')->willReturn(1);
        $this->stepUpServiceMock->method('getSessionState')->willReturn(\App\Domain\Enum\SessionState::ACTIVE);
        $this->stepUpServiceMock->method('hasGrant')->willReturn(true);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admins/create')
            ->withCookieParams(['auth_token' => 'valid-session-token'])
            ->withHeader('Accept', 'application/json');

        try {
            $response = $this->app->handle($request);
            $this->assertSame(500, $response->getStatusCode());
        } catch (\Throwable $e) {
             $this->assertTrue(true);
        }

        $pdo = MySQLTestHelper::pdo();
        $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $this->assertEquals(0, $count, 'Admins table should be empty after rollback');
    }

    public function testContainerWiring(): void
    {
        $container = Container::create();
        $container->set(\PDO::class, MySQLTestHelper::pdo());

        $controller = $container->get(\App\Http\Controllers\AdminController::class);

        $this->assertInstanceOf(\App\Http\Controllers\AdminController::class, $controller);
    }
}
