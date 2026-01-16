<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Context\RequestContext;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Service\AuthorizationService;
use App\Infrastructure\Repository\AdminRoleRepository;
use App\Infrastructure\Repository\PdoAdminDirectPermissionRepository;
use App\Infrastructure\Repository\PdoSystemOwnershipRepository;
use App\Infrastructure\Repository\RolePermissionRepository;
use App\Infrastructure\Repository\SecurityEventRepository;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use Tests\Support\MySQLTestHelper;

final class AuthorizationServiceSecurityEventsTest extends TestCase
{
    private PDO $pdo;
    private AuthorizationService $service;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();
        $this->registerSqliteNowFunction();
        $this->createTables();
        $this->truncateTables();

        $this->service = new AuthorizationService(
            new AdminRoleRepository($this->pdo),
            new RolePermissionRepository($this->pdo),
            new PdoAdminDirectPermissionRepository($this->pdo),
            new SecurityEventRepository($this->pdo),
            new PdoSystemOwnershipRepository($this->pdo)
        );
    }

    public function test_permission_denied_logs_security_event_and_returns_403(): void
    {
        $this->seedPermission('reports.view');

        $context = new RequestContext('req-1', '127.0.0.1', 'PHPUnit');
        $response = new Response();

        try {
            $this->service->checkPermission(1, 'reports.view', $context);
            $response = $response->withStatus(200);
        } catch (PermissionDeniedException) {
            $response = $response->withStatus(403);
        }

        $this->assertSame(403, $response->getStatusCode());

        $row = $this->pdo->query('SELECT actor_type, actor_id, event_type, severity, request_id, metadata FROM security_events')
            ->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            $this->fail('Expected security event row to exist.');
        }

        $this->assertSame('admin', $row['actor_type']);
        $this->assertSame(1, (int)$row['actor_id']);
        $this->assertSame('permission_denied', $row['event_type']);
        $this->assertSame('warning', $row['severity']);
        $this->assertSame('req-1', $row['request_id']);

        /** @var array{severity: string, reason: string, permission: string} $loggedContext */
        $loggedContext = json_decode((string)$row['metadata'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('missing_permission', $loggedContext['reason']);
        $this->assertSame('reports.view', $loggedContext['permission']);
    }

    public function test_permission_granted_allows_access_without_security_event(): void
    {
        $permissionId = $this->seedPermission('reports.view');

        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed, expires_at)
             VALUES (:admin_id, :permission_id, :is_allowed, :expires_at)'
        );
        $stmt->execute([
            ':admin_id' => 1,
            ':permission_id' => $permissionId,
            ':is_allowed' => 1,
            ':expires_at' => null,
        ]);

        $context = new RequestContext('req-2', '127.0.0.1', 'PHPUnit');
        $response = new Response();

        try {
            $this->service->checkPermission(1, 'reports.view', $context);
            $response = $response->withStatus(200);
        } catch (PermissionDeniedException) {
            $response = $response->withStatus(403);
        }

        $this->assertSame(200, $response->getStatusCode());

        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM security_events')->fetchColumn();
        $this->assertSame(0, $count);
    }

    private function seedPermission(string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO permissions (name) VALUES (:name)');
        $stmt->execute([':name' => $name]);

        return (int)$this->pdo->lastInsertId();
    }

    private function truncateTables(): void
    {
        foreach ([
            'permissions',
            'admin_direct_permissions',
            'admin_roles',
            'role_permissions',
            'system_ownership',
            'audit_outbox',
            'security_events',
        ] as $table) {
            MySQLTestHelper::truncate($table);
        }
    }

    private function createTables(): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS admin_direct_permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    admin_id INTEGER NOT NULL,
                    permission_id INTEGER NOT NULL,
                    is_allowed INTEGER NOT NULL,
                    granted_at DATETIME NULL,
                    expires_at DATETIME NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS admin_roles (
                    admin_id INTEGER NOT NULL,
                    role_id INTEGER NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS role_permissions (
                    role_id INTEGER NOT NULL,
                    permission_id INTEGER NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS system_ownership (
                    admin_id INTEGER NOT NULL
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
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS admin_direct_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                permission_id INT NOT NULL,
                is_allowed TINYINT(1) NOT NULL,
                granted_at DATETIME NULL,
                expires_at DATETIME NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS admin_roles (
                admin_id INT NOT NULL,
                role_id INT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS system_ownership (
                admin_id INT NOT NULL
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
    }

    private function registerSqliteNowFunction(): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            return;
        }

        if (!method_exists($this->pdo, 'sqliteCreateFunction')) {
            return;
        }

        $this->pdo->sqliteCreateFunction('NOW', static function (): string {
            return (new DateTimeImmutable())->format('Y-m-d H:i:s');
        });
    }
}
