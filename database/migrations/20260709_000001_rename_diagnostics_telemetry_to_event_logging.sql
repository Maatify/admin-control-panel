-- Rename legacy DiagnosticsTelemetry table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when diagnostics_telemetry exists and
--     maa_event_logging_diagnostics_telemetry does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'diagnostics_telemetry'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_diagnostics_telemetry'
);

SET @rename_diagnostics_telemetry_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `diagnostics_telemetry` TO `maa_event_logging_diagnostics_telemetry`',
    'SELECT ''DiagnosticsTelemetry table rename skipped'' AS migration_status'
);

PREPARE rename_diagnostics_telemetry_stmt FROM @rename_diagnostics_telemetry_sql;
EXECUTE rename_diagnostics_telemetry_stmt;
DEALLOCATE PREPARE rename_diagnostics_telemetry_stmt;
