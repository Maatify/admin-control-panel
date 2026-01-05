<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface RememberMeRepositoryInterface
{
    /**
     * Persists a new remember-me token.
     *
     * @param int $adminId
     * @param string $selector
     * @param string $hashedValidator
     * @param string $userAgentHash
     * @param \DateTimeImmutable $expiresAt
     * @return void
     */
    public function save(
        int $adminId,
        string $selector,
        string $hashedValidator,
        string $userAgentHash,
        \DateTimeImmutable $expiresAt
    ): void;

    /**
     * Finds a token record by selector.
     *
     * @param string $selector
     * @return array{id: int, admin_id: int, selector: string, hashed_validator: string, user_agent_hash: string, expires_at: string}|null
     */
    public function findBySelector(string $selector): ?array;

    /**
     * Deletes a token by selector (rotation/revocation).
     *
     * @param string $selector
     * @return void
     */
    public function deleteBySelector(string $selector): void;

    /**
     * Revokes all tokens for a specific admin (e.g., password reset, security breach).
     *
     * @param int $adminId
     * @return void
     */
    public function deleteAllByAdminId(int $adminId): void;
}
