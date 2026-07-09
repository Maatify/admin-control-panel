-- Rename legacy AuthoritativeAudit log table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when authoritative_audit_log exists and
--     maa_event_logging_authoritative_audit_log does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'authoritative_audit_log'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_authoritative_audit_log'
);

SET @rename_authoritative_audit_log_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `authoritative_audit_log` TO `maa_event_logging_authoritative_audit_log`',
    'SELECT ''AuthoritativeAudit log table rename skipped'' AS migration_status'
);

PREPARE rename_authoritative_audit_log_stmt FROM @rename_authoritative_audit_log_sql;
EXECUTE rename_authoritative_audit_log_stmt;
DEALLOCATE PREPARE rename_authoritative_audit_log_stmt;
