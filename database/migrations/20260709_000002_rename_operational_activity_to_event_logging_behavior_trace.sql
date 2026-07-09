-- Rename legacy BehaviorTrace table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when operational_activity exists and
--     maa_event_logging_behavior_trace does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'operational_activity'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_behavior_trace'
);

SET @rename_behavior_trace_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `operational_activity` TO `maa_event_logging_behavior_trace`',
    'SELECT ''BehaviorTrace table rename skipped'' AS migration_status'
);

PREPARE rename_behavior_trace_stmt FROM @rename_behavior_trace_sql;
EXECUTE rename_behavior_trace_stmt;
DEALLOCATE PREPARE rename_behavior_trace_stmt;
