<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 02:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 * @note        Repository contract for i18n_key_stats table.
 *               This table is DERIVED and non-authoritative.
 *               Used for fast per-key aggregation (grid performance).
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

interface KeyStatsRepositoryInterface
{
    /**
     * Create stats row when key is created.
     *
     * Must initialize:
     * translated_count = 0
     *
     * Should be idempotent-safe (INSERT IGNORE / ON DUPLICATE no-op).
     */
    public function createForKey(int $keyId): void;

    /**
     * Remove stats row when key is deleted.
     *
     * Safe to call even if row does not exist.
     */
    public function deleteForKey(int $keyId): void;

    /**
     * Increment translated counter for a key.
     *
     * Must be atomic.
     */
    public function incrementTranslated(int $keyId): void;

    /**
     * Decrement translated counter for a key.
     *
     * Must never go below zero.
     * Implementation should guard against negative values.
     */
    public function decrementTranslated(int $keyId): void;

    /**
     * Set translated count explicitly.
     *
     * Used for:
     * - Full rebuild
     * - Repair
     */
    public function setTranslatedCount(
        int $keyId,
        int $translatedCount
    ): void;

    /**
     * Get translated count for key.
     *
     * Returns 0 if row does not exist.
     */
    public function getTranslatedCount(int $keyId): int;

    /**
     * Bulk rebuild for a key.
     *
     * Should calculate translated_count
     * from i18n_translations table.
     *
     * Used in:
     * - Repair mode
     * - Migration
     * - Integrity validation
     */
    public function rebuildForKey(int $keyId): void;

    public function truncate(): void;
    public function rebuildAll(): void;
}
