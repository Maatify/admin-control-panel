<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface;
use RuntimeException;

final readonly class MysqlDomainLanguageSummaryRepository
    implements DomainLanguageSummaryRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /* ==========================================================
     * INCREMENTAL
     * ========================================================== */

    public function incrementTotalKeys(string $scope, string $domain): void
    {
        $sql = 'INSERT INTO i18n_domain_language_summary
                (scope, domain, language_id, total_keys, translated_count, missing_count)
            SELECT
                :scope, :domain, l.id, 1, 0, 1
            FROM languages l
            ON DUPLICATE KEY UPDATE
                total_keys = total_keys + 1,
                missing_count = missing_count + 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'  => $scope,
            'domain' => $domain,
        ]);
    }

    public function decrementTotalKeys(string $scope, string $domain): void
    {
        // safest: derived table → rebuild this scope+domain instead of guessing deltas
        $this->rebuildScopeDomain($scope, $domain);
    }

    public function incrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void {
        // ensure row exists (idempotent)
        $this->ensureRowExists($scope, $domain, $languageId);

        $stmt = $this->pdo->prepare(
            'UPDATE i18n_domain_language_summary
         SET translated_count = translated_count + 1,
             missing_count = IF(missing_count > 0, missing_count - 1, 0)
         WHERE scope = :scope
           AND domain = :domain
           AND language_id = :language_id'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'       => $scope,
            'domain'      => $domain,
            'language_id' => $languageId,
        ]);
    }


    public function decrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void {
        // ensure row exists (idempotent)
        $this->ensureRowExists($scope, $domain, $languageId);

        $stmt = $this->pdo->prepare(
            'UPDATE i18n_domain_language_summary
         SET translated_count = IF(translated_count > 0, translated_count - 1, 0),
             missing_count = missing_count + 1
         WHERE scope = :scope
           AND domain = :domain
           AND language_id = :language_id'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'       => $scope,
            'domain'      => $domain,
            'language_id' => $languageId,
        ]);
    }

    /* ==========================================================
     * DIRECT OPS
     * ========================================================== */

    public function truncate(): void
    {
        $result = $this->pdo->exec('TRUNCATE TABLE i18n_domain_language_summary');

        if ($result === false) {
            throw new RuntimeException('Failed to truncate i18n_domain_language_summary.');
        }
    }

    public function insertRow(
        string $scope,
        string $domain,
        int $languageId,
        int $totalKeys,
        int $translatedCount
    ): void {
        $sql = 'INSERT INTO i18n_domain_language_summary
            (scope, domain, language_id, total_keys, translated_count, missing_count)
            VALUES
            (:scope, :domain, :language_id, :total_keys, :translated_count, :missing_count)';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'            => $scope,
            'domain'           => $domain,
            'language_id'      => $languageId,
            'total_keys'       => $totalKeys,
            'translated_count' => $translatedCount,
            'missing_count'    => max(0, $totalKeys - $translatedCount),
        ]);
    }

    public function ensureRowExists(
        string $scope,
        string $domain,
        int $languageId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO i18n_domain_language_summary
             (scope, domain, language_id, total_keys, translated_count, missing_count)
             VALUES (:scope, :domain, :language_id, 0, 0, 0)
             ON DUPLICATE KEY UPDATE scope = scope'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'       => $scope,
            'domain'      => $domain,
            'language_id' => $languageId,
        ]);
    }

    /* ==========================================================
     * REBUILD (SQL-Driven)
     * ========================================================== */

    public function rebuildAll(): void
    {
        $sql = '
        INSERT INTO i18n_domain_language_summary
            (scope, domain, language_id, total_keys, translated_count, missing_count)
        SELECT
            k.scope,
            k.domain,
            l.id AS language_id,
            COUNT(DISTINCT k.id) AS total_keys,
            COUNT(t.id) AS translated_count,
            COUNT(DISTINCT k.id) - COUNT(t.id) AS missing_count
        FROM i18n_keys k
        CROSS JOIN languages l
        LEFT JOIN i18n_translations t
            ON t.key_id = k.id
            AND t.language_id = l.id
        GROUP BY k.scope, k.domain, l.id
        ON DUPLICATE KEY UPDATE
            total_keys = VALUES(total_keys),
            translated_count = VALUES(translated_count),
            missing_count = VALUES(missing_count)
    ';

        $result = $this->pdo->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Failed to rebuild i18n_domain_language_summary.');
        }
    }

    public function rebuildScopeDomain(string $scope, string $domain): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM i18n_domain_language_summary
         WHERE scope = :scope
           AND domain = :domain'
        );

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'  => $scope,
            'domain' => $domain,
        ]);

        $sql = '
        INSERT INTO i18n_domain_language_summary
            (scope, domain, language_id, total_keys, translated_count, missing_count)
        SELECT
            :scope AS scope,
            :domain AS domain,
            l.id AS language_id,
            COUNT(DISTINCT k.id) AS total_keys,
            COUNT(t.id) AS translated_count,
            COUNT(DISTINCT k.id) - COUNT(t.id) AS missing_count
        FROM languages l
        LEFT JOIN i18n_keys k
            ON k.scope = :scope
           AND k.domain = :domain
        LEFT JOIN i18n_translations t
            ON t.key_id = k.id
           AND t.language_id = l.id
        GROUP BY l.id
    ';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope'  => $scope,
            'domain' => $domain,
        ]);
    }

    /* ==========================================================
     * READ
     * ========================================================== */
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
    ): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT total_keys, translated_count, missing_count
         FROM i18n_domain_language_summary
         WHERE scope = :scope
           AND domain = :domain
           AND language_id = :language_id'
        );

        if (!$stmt instanceof PDOStatement) {
            return null;
        }

        $stmt->execute([
            'scope'       => $scope,
            'domain'      => $domain,
            'language_id' => $languageId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array{total_keys: numeric-string|int, translated_count: numeric-string|int, missing_count: numeric-string|int} $row */

        return [
            'total_keys'       => (int) $row['total_keys'],
            'translated_count' => (int) $row['translated_count'],
            'missing_count'    => (int) $row['missing_count'],
        ];
    }

}
