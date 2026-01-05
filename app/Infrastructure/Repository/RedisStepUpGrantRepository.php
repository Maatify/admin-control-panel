<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\DTO\StepUpGrant;
use App\Domain\Enum\Scope;
use DateTimeImmutable;
use Redis;
use RedisException;
use RuntimeException;

class RedisStepUpGrantRepository implements StepUpGrantRepositoryInterface
{
    private Redis $redis;

    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        if (!class_exists('Redis')) {
            throw new RuntimeException('Redis extension is required for Step-Up Grants.');
        }
        $this->redis = new Redis();
        try {
            // Fail-closed: If we can't connect, we can't verify grants.
            if (!$this->redis->connect($host, $port, 1.0)) {
                 throw new RuntimeException('Could not connect to Redis.');
            }
        } catch (RedisException $e) {
            throw new RuntimeException('Redis connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function save(StepUpGrant $grant): void
    {
        $key = $this->getKey($grant->adminId, $grant->sessionId, $grant->scope);
        $ttl = $grant->expiresAt->getTimestamp() - time();

        if ($ttl <= 0) {
            return;
        }

        $data = json_encode($grant);
        if ($data === false) {
             throw new RuntimeException('Failed to encode grant data.');
        }

        try {
            $this->redis->setex($key, $ttl, $data);
        } catch (RedisException $e) {
            // Fail-closed: If we can't save the grant, the user cannot proceed.
            throw new RuntimeException('Failed to save Step-Up Grant to Redis.', 0, $e);
        }
    }

    public function find(int $adminId, string $sessionId, Scope $scope): ?StepUpGrant
    {
        $key = $this->getKey($adminId, $sessionId, $scope);
        try {
            $data = $this->redis->get($key);
        } catch (RedisException $e) {
            // Fail-closed: If Redis fails, assume no grant exists.
            // Wait, "Redis failure tested (fail-closed)".
            // If we throw here, the middleware will catch 500. This is fail-closed.
            // If we return null, it means "denied". This is also fail-closed.
            // But returning null is cleaner for the caller than crashing.
            // However, distinguishing between "No Grant" and "System Error" is important for monitoring.
            // But for security, result is DENY.
            return null;
        }

        if ($data === false || !is_string($data)) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return null;
        }

        // Validation of decoded data structure is tricky without strong typing on decode,
        // but we rely on what we saved.
        // We need to reconstruct the DTO.

        try {
            /** @var array{admin_id: int, session_id: string, scope: string, issued_at: string, expires_at: string, single_use: bool, context_snapshot: array<string, mixed>} $decoded */
            return new StepUpGrant(
                (int)$decoded['admin_id'],
                (string)$decoded['session_id'],
                Scope::from((string)$decoded['scope']),
                new DateTimeImmutable((string)$decoded['issued_at']),
                new DateTimeImmutable((string)$decoded['expires_at']),
                (bool)$decoded['single_use'],
                (array)$decoded['context_snapshot']
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function revoke(int $adminId, string $sessionId, Scope $scope): void
    {
        $key = $this->getKey($adminId, $sessionId, $scope);
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            // Log error?
        }
    }

    private function getKey(int $adminId, string $sessionId, Scope $scope): string
    {
        return sprintf("stepup:%d:%s:%s", $adminId, $sessionId, $scope->value);
    }
}
