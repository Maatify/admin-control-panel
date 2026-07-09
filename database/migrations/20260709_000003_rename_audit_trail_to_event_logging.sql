-- Rename legacy AuditTrail table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when audit_trail exists and
--     maa_event_logging_audit_trail does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'audit_trail'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_audit_trail'
);

SET @rename_audit_trail_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `audit_trail` TO `maa_event_logging_audit_trail`',
    'SELECT ''AuditTrail table rename skipped'' AS migration_status'
);

PREPARE rename_audit_trail_stmt FROM @rename_audit_trail_sql;
EXECUTE rename_audit_trail_stmt;
DEALLOCATE PREPARE rename_audit_trail_stmt;
