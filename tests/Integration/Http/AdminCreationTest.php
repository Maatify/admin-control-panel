<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

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
        $pdo->exec("INSERT INTO admins (id, display_name, status) VALUES (1, 'Super Admin', 'ACTIVE')");

        $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES (1, 'admin.create', 'Create Admin')");

        $pdo->exec("INSERT INTO admin_direct_permissions (admin_id, permission_id, is_allowed) VALUES (1, 1, 1)");

        $token = 'test-session-token';
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('$tokenHash', 1, '$expires')");

        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');

        // Step-Up login
        $pdo->exec("INSERT INTO step_up_grants 
            (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) 
            VALUES 
            (1, '$tokenHash', 'login', '$riskHash', '$issued', '$expires', 0)");

        // Step-Up for admin.create
        $pdo->exec("INSERT INTO step_up_grants 
            (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) 
            VALUES 
            (1, '$tokenHash', 'admin.create', '$riskHash', '$issued', '$expires', 0)");

        $request = $this->createRequest('POST', '/api/admins/create', [
            'display_name' => 'New Admin',
            'email' => 'newadmin@example.com'
        ])->withCookieParams(['auth_token' => $token]);

        $response = $this->app->handle($request);

        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode(), 'Expected 200 OK. Body: ' . $body);

        $json = json_decode($body, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('admin_id', $json);

        $newAdminId = $json['admin_id'];

        $this->assertDatabaseHas('admins', [
            'id' => $newAdminId,
            'status' => 'ACTIVE'
        ]);

        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        if ($stmt === false) {
            $this->fail('Query failed');
        }

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

        $pdo->exec("INSERT INTO permissions (id, name, display_name) VALUES (1, 'admin.create', 'Create Admin')");

        $token = 'low-priv-token';
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('$tokenHash', 2, '$expires')");

        $riskHash = hash('sha256', '0.0.0.0|unknown');
        $issued = date('Y-m-d H:i:s');

        // Step-Up login
        $pdo->exec("INSERT INTO step_up_grants 
            (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) 
            VALUES 
            (2, '$tokenHash', 'login', '$riskHash', '$issued', '$expires', 0)");

        // 🔥 مهم: إضافة Step-Up لـ admin.create حتى نصل لمرحلة Authorization
        $pdo->exec("INSERT INTO step_up_grants 
            (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use) 
            VALUES 
            (2, '$tokenHash', 'admin.create', '$riskHash', '$issued', '$expires', 0)");

        $request = $this->createRequest('POST', '/api/admins/create', [
            'display_name' => 'New Admin',
            'email' => 'newadmin@example.com'
        ])->withCookieParams(['auth_token' => $token]);

        $response = $this->app->handle($request);

        $body = (string) $response->getBody();

        $this->assertSame(403, $response->getStatusCode(), "Expected 403 Forbidden. Body: {$body}");

        $json = json_decode($body, true);

        $this->assertIsArray($json);

        $this->assertFalse($json['success']);

        $this->assertArrayHasKey('error', $json);
        $this->assertIsArray($json['error']);

        $this->assertEquals('PERMISSION_DENIED', $json['error']['code']);
        $this->assertEquals('AUTHORIZATION', $json['error']['category']);
        $this->assertFalse($json['error']['retryable']);

        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        if ($stmt === false) {
            $this->fail('Query failed');
        }

        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
    }
}