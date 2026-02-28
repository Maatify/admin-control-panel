<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 02:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 * @note        MySQL implementation for i18n_key_stats repository.
 *               Table is DERIVED (non-authoritative).
 *               All mutations must be executed inside same TX by caller.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use Maatify\I18n\Contract\KeyStatsRepositoryInterface;
use PDO;
use RuntimeException;

final readonly class MysqlKeyStatsRepository implements KeyStatsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /* ==========================================================
     * BASIC MUTATIONS
     * ========================================================== */

    public function createForKey(int $keyId): void
    {
        $stmt = $this->pdo->prepare(
            "
            INSERT INTO i18n_key_stats (key_id, translated_count)
            VALUES (:key_id, 0)
            ON DUPLICATE KEY UPDATE key_id = key_id
            "
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare createForKey statement.');
        }

        $stmt->execute(['key_id' => $keyId]);
    }

    public function deleteForKey(int $keyId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM i18n_key_stats WHERE key_id = :key_id"
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare deleteForKey statement.');
        }

        $stmt->execute(['key_id' => $keyId]);
    }

    public function incrementTranslated(int $keyId): void
    {
        $stmt = $this->pdo->prepare(
            "
        INSERT INTO i18n_key_stats (key_id, translated_count)
        VALUES (:key_id, 1)
        ON DUPLICATE KEY UPDATE translated_count = translated_count + 1
        "
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare incrementTranslated statement.');
        }

        $stmt->execute(['key_id' => $keyId]);
    }

    public function decrementTranslated(int $keyId): void
    {
        $stmt = $this->pdo->prepare(
            "
        INSERT INTO i18n_key_stats (key_id, translated_count)
        VALUES (:key_id, 0)
        ON DUPLICATE KEY UPDATE translated_count =
            CASE
                WHEN translated_count > 0
                THEN translated_count - 1
                ELSE 0
            END
        "
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare decrementTranslated statement.');
        }

        $stmt->execute(['key_id' => $keyId]);
    }

    public function setTranslatedCount(
        int $keyId,
        int $translatedCount
    ): void {
        if ($translatedCount < 0) {
            $translatedCount = 0;
        }

        $stmt = $this->pdo->prepare(
            "
            INSERT INTO i18n_key_stats (key_id, translated_count)
            VALUES (:key_id, :count)
            ON DUPLICATE KEY UPDATE translated_count = :count
            "
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare setTranslatedCount statement.');
        }

        $stmt->execute([
            'key_id' => $keyId,
            'count'  => $translatedCount,
        ]);
    }

    public function getTranslatedCount(int $keyId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT translated_count FROM i18n_key_stats WHERE key_id = :key_id"
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare getTranslatedCount statement.');
        }

        $stmt->execute(['key_id' => $keyId]);

        $value = $stmt->fetchColumn();

        return $value === false ? 0 : (int)$value;
    }

    /* ==========================================================
     * REBUILD OPERATIONS (SQL-DRIVEN)
     * ========================================================== */

    /**
     * Truncate derived table.
     * Used in full rebuild.
     */
    public function truncate(): void
    {
        $result = $this->pdo->exec("TRUNCATE TABLE i18n_key_stats");

        if ($result === false) {
            throw new RuntimeException('Failed to truncate i18n_key_stats.');
        }
    }

    /**
     * Rebuild a single key using authoritative i18n_translations table.
     */
    public function rebuildForKey(int $keyId): void
    {
        $stmt = $this->pdo->prepare(
            "
            INSERT INTO i18n_key_stats (key_id, translated_count)
            SELECT
                k.id,
                COUNT(t.id)
            FROM i18n_keys k
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id
            WHERE k.id = :key_id
            GROUP BY k.id
            ON DUPLICATE KEY UPDATE translated_count = VALUES(translated_count)
            "
        );

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare rebuildForKey statement.');
        }

        $stmt->execute(['key_id' => $keyId]);
    }

    /**
     * Full rebuild for entire table.
     *
     * Pure SQL aggregation:
     * - No PHP loops
     * - No N+1
     */
    public function rebuildAll(): void
    {
        $sql = "
        INSERT INTO i18n_key_stats (key_id, translated_count)
        SELECT
            k.id,
            COUNT(t.id) AS translated_count
        FROM i18n_keys k
        LEFT JOIN i18n_translations t
            ON t.key_id = k.id
        GROUP BY k.id
        ON DUPLICATE KEY UPDATE
            translated_count = VALUES(translated_count)
    ";

        $result = $this->pdo->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Failed to rebuild i18n_key_stats.');
        }
    }

}
