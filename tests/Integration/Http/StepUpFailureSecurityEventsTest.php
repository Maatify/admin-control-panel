<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Enum\Scope;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\StepUpService;
use App\Infrastructure\Audit\PdoAuthoritativeAuditWriter;
use App\Infrastructure\Audit\PdoTelemetryAuditLogger;
use App\Infrastructure\Repository\FileTotpSecretRepository;
use App\Infrastructure\Repository\PdoStepUpGrantRepository;
use App\Infrastructure\Repository\SecurityEventRepository;
use App\Infrastructure\Service\Google2faTotpService;
use App\Domain\Telemetry\Recorder\TelemetryRecorder;
use App\Http\Controllers\StepUpController;
use App\Http\Middleware\ScopeGuardMiddleware;
use App\Modules\Telemetry\Infrastructure\Mysql\TelemetryLoggerMysqlRepository;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Validator\RespectValidator;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Tests\Support\MySQLTestHelper;

final class StepUpFailureSecurityEventsTest extends TestCase
{
    private PDO $pdo;
    private StepUpService $stepUpService;
    private StepUpController $stepUpController;
    private FileTotpSecretRepository $totpSecretRepository;
    private string $totpDirectory;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        $this->createTables();
        $this->truncateTables();
        $this->seedSystemState();

        $this->totpDirectory = sys_get_temp_dir() . '/totp-test-' . bin2hex(random_bytes(6));
        $this->totpSecretRepository = new FileTotpSecretRepository($this->totpDirectory);

        $telemetryLogger = new TelemetryLoggerMysqlRepository($this->pdo);
        $telemetryRecorder = new TelemetryRecorder($telemetryLogger);
        $telemetryFactory = new HttpTelemetryRecorderFactory($telemetryRecorder);

        $this->stepUpService = $this->buildStepUpService();

        $validator = new RespectValidator();
        $validationGuard = new ValidationGuard($validator);

        $this->stepUpController = new StepUpController(
            $this->stepUpService,
            $validationGuard,
            $telemetryFactory
        );
    }

    protected function tearDown(): void
    {
        $this->deleteTotpDirectory();
    }

    public function test_stepup_not_enrolled_logs_security_event_and_returns_403(): void
    {
        $request = $this->buildStepUpRequest('123456', 'token-not-enrolled');

        $response = $this->stepUpController->verify($request, new Response());

        $this->assertSame(403, $response->getStatusCode());

        $row = $this->pdo->query(
            'SELECT actor_type, actor_id, event_type, severity, request_id, metadata FROM security_events'
        )->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            $this->fail('Expected security event row to exist.');
        }

        $this->assertSame('admin', $row['actor_type']);
        $this->assertSame(1, (int)$row['actor_id']);
        $this->assertSame('stepup_primary_failed', $row['event_type']);
        $this->assertSame('warning', $row['severity']);
        $this->assertSame('req-stepup', $row['request_id']);

        /** @var array{severity: string, reason: string} $context */
        $context = json_decode((string)$row['metadata'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('no_totp_enrolled', $context['reason']);
    }

    public function test_stepup_invalid_totp_logs_security_event_and_returns_403(): void
    {
        $secret = (new Google2faTotpService())->generateSecret();
        $this->totpSecretRepository->save(1, $secret);

        $request = $this->buildStepUpRequest('invalid-code', 'token-invalid');

        $response = $this->stepUpController->verify($request, new Response());

        $this->assertSame(403, $response->getStatusCode());

        $row = $this->pdo->query(
            'SELECT actor_type, actor_id, event_type, severity, request_id, metadata FROM security_events'
        )->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            $this->fail('Expected security event row to exist.');
        }

        $this->assertSame('admin', $row['actor_type']);
        $this->assertSame(1, (int)$row['actor_id']);
        $this->assertSame('stepup_primary_failed', $row['event_type']);
        $this->assertSame('warning', $row['severity']);
        $this->assertSame('req-stepup', $row['request_id']);

        /** @var array{severity: string, reason: string} $context */
        $context = json_decode((string)$row['metadata'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('invalid_code', $context['reason']);
    }

    public function test_stepup_risk_mismatch_logs_security_event_and_returns_403(): void
    {
        $adminId = 1;
        $token = 'token-risk';
        $sessionId = hash('sha256', $token);

        $this->pdo->prepare(
            'INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use, context_snapshot)
             VALUES (:admin_id, :session_id, :scope, :risk_context_hash, :issued_at, :expires_at, :single_use, :context_snapshot)'
        )->execute([
            ':admin_id' => $adminId,
            ':session_id' => $sessionId,
            ':scope' => Scope::SECURITY->value,
            ':risk_context_hash' => hash('sha256', '10.0.0.1|ua-one'),
            ':issued_at' => (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s'),
            ':expires_at' => (new DateTimeImmutable('+10 minutes'))->format('Y-m-d H:i:s'),
            ':single_use' => 0,
            ':context_snapshot' => json_encode([], JSON_THROW_ON_ERROR),
        ]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory
            ->createServerRequest('GET', '/protected')
            ->withCookieParams(['auth_token' => $token])
            ->withAttribute(AdminContext::class, new AdminContext($adminId))
            ->withAttribute(RequestContext::class, new RequestContext('req-risk', '10.0.0.2', 'ua-two'));

        $route = new Route(['GET'], '/protected', static function () {
        }, new ResponseFactory());
        $route->setName('security');
        $request = $request->withAttribute(RouteContext::ROUTE, $route);

        $middleware = new ScopeGuardMiddleware($this->stepUpService);
        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return (new Response())->withStatus(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());

        $row = $this->pdo->query(
            'SELECT actor_type, actor_id, event_type, severity, request_id, metadata FROM security_events'
        )->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            $this->fail('Expected security event row to exist.');
        }

        $this->assertSame('admin', $row['actor_type']);
        $this->assertSame(1, (int)$row['actor_id']);
        $this->assertSame('stepup_risk_mismatch', $row['event_type']);
        $this->assertSame('error', $row['severity']);
        $this->assertSame('req-risk', $row['request_id']);

        /** @var array{severity: string, reason: string} $context */
        $context = json_decode((string)$row['metadata'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('context_changed', $context['reason']);
    }

    private function buildStepUpRequest(string $code, string $token): \Psr\Http\Message\ServerRequestInterface
    {
        $requestFactory = new ServerRequestFactory();

        return $requestFactory
            ->createServerRequest('POST', '/auth/step-up')
            ->withParsedBody(['code' => $code])
            ->withCookieParams(['auth_token' => $token])
            ->withAttribute(AdminContext::class, new AdminContext(1))
            ->withAttribute(RequestContext::class, new RequestContext('req-stepup', '127.0.0.1', 'PHPUnit'));
    }

    private function buildStepUpService(): StepUpService
    {
        $grantRepository = new PdoStepUpGrantRepository($this->pdo);
        $totpService = new Google2faTotpService();
        $auditLogger = new PdoTelemetryAuditLogger($this->pdo);
        $securityLogger = new SecurityEventRepository($this->pdo);
        $outboxWriter = new PdoAuthoritativeAuditWriter($this->pdo);

        $config = new AdminConfigDTO(
            appEnv: 'test',
            appDebug: true,
            timezone: 'UTC',
            passwordActivePepperId: 'pepper',
            dbHost: 'localhost',
            dbName: 'test',
            dbUser: 'root',
            isRecoveryMode: false,
            activeKeyId: null,
            hasCryptoKeyRing: false,
            hasPasswordPepperRing: false
        );

        $recoveryState = new RecoveryStateService(
            $outboxWriter,
            $securityLogger,
            $this->pdo,
            $config,
            str_repeat('a', 32)
        );

        return new StepUpService(
            $grantRepository,
            $this->totpSecretRepository,
            $totpService,
            $auditLogger,
            $securityLogger,
            $outboxWriter,
            $recoveryState,
            $this->pdo
        );
    }

    private function truncateTables(): void
    {
        foreach ([
            'security_events',
            'step_up_grants',
            'system_state',
            'audit_outbox',
        ] as $table) {
            MySQLTestHelper::truncate($table);
        }
    }

    private function seedSystemState(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO system_state (state_key, state_value, updated_at)
             VALUES (:state_key, :state_value, :updated_at)'
        );
        $stmt->execute([
            ':state_key' => 'recovery_mode',
            ':state_value' => RecoveryStateService::SYSTEM_STATE_ACTIVE,
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    private function createTables(): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS step_up_grants (
                    admin_id INTEGER NOT NULL,
                    session_id TEXT NOT NULL,
                    scope TEXT NOT NULL,
                    risk_context_hash TEXT NOT NULL,
                    issued_at DATETIME NOT NULL,
                    expires_at DATETIME NOT NULL,
                    single_use INTEGER NOT NULL DEFAULT 0,
                    context_snapshot TEXT NULL,
                    PRIMARY KEY (admin_id, session_id, scope)
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS system_state (
                    state_key TEXT PRIMARY KEY,
                    state_value TEXT NOT NULL,
                    updated_at DATETIME NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS audit_outbox (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    actor_id INTEGER NULL,
                    action TEXT NOT NULL,
                    target_type TEXT NOT NULL,
                    target_id INTEGER NULL,
                    risk_level TEXT NOT NULL,
                    payload TEXT NOT NULL,
                    correlation_id TEXT NOT NULL,
                    created_at DATETIME NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS security_events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    actor_type VARCHAR(32) NOT NULL,
                    actor_id INTEGER NULL,
                    event_type VARCHAR(100) NOT NULL,
                    severity VARCHAR(20) NOT NULL,
                    request_id VARCHAR(64) NULL,
                    route_name VARCHAR(255) NULL,
                    metadata TEXT NOT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    occurred_at DATETIME NOT NULL
                )'
            );
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS step_up_grants (
                admin_id INT NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                scope VARCHAR(64) NOT NULL,
                risk_context_hash VARCHAR(64) NOT NULL,
                issued_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                single_use TINYINT(1) NOT NULL DEFAULT 0,
                context_snapshot JSON NULL,
                PRIMARY KEY (admin_id, session_id, scope)
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS system_state (
                state_key VARCHAR(64) PRIMARY KEY,
                state_value VARCHAR(64) NOT NULL,
                updated_at DATETIME NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS audit_outbox (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                actor_id BIGINT NULL,
                action VARCHAR(128) NOT NULL,
                target_type VARCHAR(64) NOT NULL,
                target_id BIGINT NULL,
                risk_level VARCHAR(16) NOT NULL,
                payload JSON NOT NULL,
                correlation_id CHAR(36) NOT NULL,
                created_at DATETIME NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS security_events (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                actor_type VARCHAR(32) NOT NULL,
                actor_id BIGINT NULL,
                event_type VARCHAR(100) NOT NULL,
                severity VARCHAR(20) NOT NULL,
                request_id VARCHAR(64) NULL,
                route_name VARCHAR(255) NULL,
                metadata JSON NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                occurred_at DATETIME NOT NULL
            )'
        );
    }

    private function deleteTotpDirectory(): void
    {
        if (!is_dir($this->totpDirectory)) {
            return;
        }

        $files = scandir($this->totpDirectory);
        if ($files !== false) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $this->totpDirectory . '/' . $file;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        rmdir($this->totpDirectory);
    }
}
