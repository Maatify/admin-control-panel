<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 * @note        Repository contract for i18n_domain_language_summary.
 *
 *               Nature:
 *               - Derived aggregation table
 *               - NON-authoritative
 *               - Fully rebuildable
 *
 *               Responsibility:
 *               - Maintain per (scope, domain, language)
 *                 translation completeness metrics.
 *
 *               Integrity Rules:
 *               - translated_count <= total_keys
 *               - missing_count = total_keys - translated_count
 *
 *               All write operations must run inside caller TX.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

interface DomainLanguageSummaryRepositoryInterface
{
    /* ==========================================================
     * INCREMENTAL MUTATIONS
     * ========================================================== */

    /**
     * total_keys++
     * missing_count++ (implicitly via derived logic)
     */
    public function incrementTotalKeys(
        string $scope,
        string $domain
    ): void;

    /**
     * total_keys--
     * translated_count/missing_count adjusted safely
     */
    public function decrementTotalKeys(
        string $scope,
        string $domain
    ): void;

    /**
     * translated_count++
     * missing_count--
     */
    public function incrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void;

    /**
     * translated_count--
     * missing_count++
     */
    public function decrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void;

    /* ==========================================================
     * DIRECT INSERT / REBUILD
     * ========================================================== */

    /**
     * Truncate entire derived table.
     *
     * Used in full rebuild.
     */
    public function truncate(): void;

    /**
     * Insert a fully calculated row.
     *
     * Used by:
     * - Full rebuild
     * - Migration
     */
    public function insertRow(
        string $scope,
        string $domain,
        int $languageId,
        int $totalKeys,
        int $translatedCount
    ): void;

    /**
     * Ensure row exists for (scope, domain, language).
     *
     * Should be idempotent-safe.
     */
    public function ensureRowExists(
        string $scope,
        string $domain,
        int $languageId
    ): void;

    /**
     * Rebuild entire table using authoritative tables.
     *
     * Must be SQL-driven.
     */
    public function rebuildAll(): void;

    /**
     * Rebuild specific scope+domain only.
     *
     * Useful for partial repair.
     */
    public function rebuildScopeDomain(
        string $scope,
        string $domain
    ): void;

    /* ==========================================================
     * READ ACCESS
     * ========================================================== */

    /**
     * Get summary row.
     *
     * Returns null if not found.
     */
    /**
     * @return array{
     *     total_keys: int,
     *     translated_count: int,
     *     missing_count: int
     * }|null
     */
    public function getRow(
        string $scope,
        string $domain,
        int $languageId
    ): ?array;
}
