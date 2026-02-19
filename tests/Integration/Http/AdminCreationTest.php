<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Maatify\AdminKernel\Domain\Exception\PermissionDeniedException;
use Tests\Support\UnifiedEndpointBase;

class AdminCreationTest extends UnifiedEndpointBase
{
    public function test_can_create_admin_with_permission(): void
    {
        $pdo = $this->pdo;
        if ($pdo === null) {
            $this->fail('PDO not initialized');
        }

        // 1. Seed Database
        // Admin
        $pdo->exec("INSERT INTO admins (id, display_name, status) VALUES (1, 'Super Admin', 'ACTIVE')");

        // Permission
        $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES (1, 'admin.create', 'Create Admin')");

        // Grant Permission
        $pdo->exec("INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed) VALUES (1, 1, 1)");

        // Session
        $token = 'test-session-token';
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('$tokenHash', 1, '$expires')");

        // Step-Up Grant (Scope: login, Risk: 0.0.0.0|unknown)
        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) VALUES (1, '$tokenHash', 'login', '$riskHash', '$issued', '$expires', 0)");

        // Step-Up Grant (Scope: admin.create) - Required for sensitive action
        $pdo->exec("INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) VALUES (1, '$tokenHash', 'admin.create', '$riskHash', '$issued', '$expires', 0)");

        // 2. Perform Request
        $request = $this->createRequest('POST', '/api/admins/create', [
            'display_name' => 'New Admin',
            'email' => 'newadmin@example.com'
        ])
            ->withCookieParams(['auth_token' => $token]);

        $response = $this->app->handle($request);

        // 3. Assert Response
        $body = (string) $response->getBody();
        $this->assertSame(200, $response->getStatusCode(), 'Expected 200 OK. Body: ' . $body);
        $json = json_decode($body, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('admin_id', $json);
        $newAdminId = $json['admin_id'];

        // 4. Assert Database Side Effect
        $this->assertDatabaseHas('admins', [
            'id' => $newAdminId,
            'status' => 'ACTIVE' // Default
        ]);

        // Ensure we have 2 admins now
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        if ($stmt === false) { $this->fail('Query failed'); }
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);
    }

    public function test_cannot_create_admin_without_permission(): void
    {
        $pdo = $this->pdo;
        if ($pdo === null) {
            $this->fail('PDO not initialized');
        }

        // 1. Seed Database (Admin without permission)
        $pdo->exec("INSERT INTO admins (id, display_name, status) VALUES (2, 'Lowly Admin', 'ACTIVE')");

        // Permission (must exist for check to proceed to authorization)
        $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES (1, 'admin.create', 'Create Admin')");

        // Session
        $token = 'low-priv-token';
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('$tokenHash', 2, '$expires')");

        // Step-Up Grant
        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) VALUES (2, '$tokenHash', 'login', '$riskHash', '$issued', '$expires', 0)");

        // 2. Perform Request
        $request = $this->createRequest('POST', '/api/admins/create', [
            'display_name' => 'New Admin',
            'email' => 'newadmin@example.com'
        ])
            ->withCookieParams(['auth_token' => $token]);

        // 3. Assert Response (403 Forbidden)
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        // Since we mapped PermissionDeniedException to 403 in bootstrap, we assert status 403
        $this->assertSame(403, $response->getStatusCode(), "Expected 403 Forbidden. Status: {$response->getStatusCode()}, Body: {$body}");

        $json = json_decode($body, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('PERMISSION_DENIED', $json['error']['code']);
        $this->assertEquals('AUTHORIZATION', $json['error']['category']);

        // Assert No New Admin Created
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        if ($stmt === false) {
            $this->fail('Query failed');
        }
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);

    }
}
