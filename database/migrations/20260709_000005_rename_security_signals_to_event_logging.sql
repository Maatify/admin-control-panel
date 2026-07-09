-- Rename legacy SecuritySignals table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when security_signals exists and
--     maa_event_logging_security_signals does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'security_signals'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_security_signals'
);

SET @rename_security_signals_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `security_signals` TO `maa_event_logging_security_signals`',
    'SELECT ''SecuritySignals table rename skipped'' AS migration_status'
);

PREPARE rename_security_signals_stmt FROM @rename_security_signals_sql;
EXECUTE rename_security_signals_stmt;
DEALLOCATE PREPARE rename_security_signals_stmt;
