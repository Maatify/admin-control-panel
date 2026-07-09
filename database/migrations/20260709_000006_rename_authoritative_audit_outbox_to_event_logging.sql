-- Rename legacy AuthoritativeAudit outbox table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when authoritative_audit_outbox exists and
--     maa_event_logging_authoritative_audit_outbox does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'authoritative_audit_outbox'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_authoritative_audit_outbox'
);

SET @rename_authoritative_audit_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `authoritative_audit_outbox` TO `maa_event_logging_authoritative_audit_outbox`',
    'SELECT ''AuthoritativeAudit outbox table rename skipped'' AS migration_status'
);

PREPARE rename_authoritative_audit_stmt FROM @rename_authoritative_audit_sql;
EXECUTE rename_authoritative_audit_stmt;
DEALLOCATE PREPARE rename_authoritative_audit_stmt;
