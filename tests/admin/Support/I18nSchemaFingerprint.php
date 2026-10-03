<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;

/**
 * Complete structural fingerprint of the CURRENT schema of a connection:
 * columns (type, nullability, default, extra, collation, generation, comment),
 * indexes, constraints, checks, foreign keys, table options and comments.
 *
 * Two schemas with an equal fingerprint are structurally identical, which is
 * how a migrated database / a Host dump is proven equal to a fresh install of
 * the Package schema.
 */
final class I18nSchemaFingerprint
{
    /**
     * @return list<string>
     */
    public static function of(PDO $pdo): array
    {
        $queries = [
            'SELECT CONCAT_WS("|", "COL", TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, IFNULL(COLUMN_DEFAULT, "~"), EXTRA, IFNULL(COLLATION_NAME, "~"), IFNULL(REPLACE(REPLACE(GENERATION_EXPRESSION, "_latin1", ""), "_utf8mb4", ""), "~"), COLUMN_COMMENT) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION',
            'SELECT CONCAT_WS("|", "IDX", TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME, NON_UNIQUE, INDEX_TYPE) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            'SELECT CONCAT_WS("|", "CON", TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            'SELECT CONCAT_WS("|", "CHK", CONSTRAINT_NAME, CHECK_CLAUSE) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() ORDER BY CONSTRAINT_NAME',
            'SELECT CONCAT_WS("|", "FK", TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            'SELECT CONCAT_WS("|", "TBL", TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME',
        ];

        $all = [];
        foreach ($queries as $query) {
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new RuntimeException('Cannot read the schema fingerprint.');
            }

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $line) {
                $all[] = is_string($line) ? $line : '';
            }
        }

        return $all;
    }
}
