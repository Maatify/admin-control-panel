<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Integration\Http\Api;

use App\Domain\Service\AuthorizationService;
use App\Http\Controllers\Api\ActivityLogQueryController;
use App\Infrastructure\Query\ListFilterResolver;
use App\Infrastructure\Reader\ActivityLog\PdoActivityLogListReader;
use App\Modules\Validation\Contracts\ValidatorInterface;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Validator\RespectValidator;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\Support\MySQLTestHelper;

final class ActivityLogQueryControllerTest extends TestCase
{
    private PDO $pdo;
    private ActivityLogQueryController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = MySQLTestHelper::pdo();
        MySQLTestHelper::truncate('activity_logs');
        $this->seed();

        // Dependencies
        $reader = new PdoActivityLogListReader($this->pdo);

        $auth = $this->createMock(AuthorizationService::class);
        $auth->method('hasPermission')->willReturn(true); // Allow access

        $validator = new RespectValidator();
        $guard = new ValidationGuard($validator);

        $resolver = new ListFilterResolver();

        $this->controller = new ActivityLogQueryController(
            $reader,
            $auth,
            $guard,
            $resolver
        );
    }

    private function seed(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activity_logs
                (action, actor_type, actor_id, entity_type, entity_id, metadata, ip_address, user_agent, request_id, occurred_at)
             VALUES
                (:action, :actor_type, :actor_id, :entity_type, :entity_id, :metadata, :ip_address, :user_agent, :request_id, :occurred_at)'
        );

        // Yesterday
        $stmt->execute([
            'action'      => 'admin.user.update',
            'actor_type'  => 'admin',
            'actor_id'    => 1,
            'entity_type' => 'user',
            'entity_id'   => 10,
            'metadata'    => json_encode(['field' => 'email'], JSON_THROW_ON_ERROR),
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'request_id'  => 'req-old',
            'occurred_at' => (new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'),
        ]);

        // Today
        $stmt->execute([
            'action'      => 'admin.login',
            'actor_type'  => 'admin',
            'actor_id'    => 2,
            'entity_type' => null,
            'entity_id'   => null,
            'metadata'    => null,
            'ip_address'  => null,
            'user_agent'  => null,
            'request_id'  => 'req-new',
            'occurred_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_activity_log_query_controller_returns_filtered_result(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/activity-logs/query')
            ->withParsedBody([
                'page' => 1,
                'per_page' => 10,
                'search' => [
                    'global' => 'login',
                ],
                'date' => [
                    'from' => date('Y-m-d'),
                    'to'   => date('Y-m-d'),
                ],
            ])
            ->withAttribute(\App\Context\AdminContext::class, new \App\Context\AdminContext(1));

        $response = ($this->controller)($request, new Response());

        $this->assertSame(200, $response->getStatusCode());

        /** @var array{data: array<int, array{action: string}>, pagination: array{filtered: int, total: int}} $payload */
        $payload = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('pagination', $payload);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('admin.login', $payload['data'][0]['action']);

        $this->assertSame(1, $payload['pagination']['filtered']);
        $this->assertSame(2, $payload['pagination']['total']);
    }
}
