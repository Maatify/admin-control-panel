-- Rename legacy DeliveryOperations table to the maatify/event-logging canonical table.
-- Safe behavior:
--   * Renames only when delivery_operations exists and
--     maa_event_logging_delivery_operations does not exist in the current database.
--   * Does not drop, recreate, truncate, or copy data.

SET @old_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'delivery_operations'
);

SET @new_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'maa_event_logging_delivery_operations'
);

SET @rename_delivery_operations_sql := IF(
    @old_table_exists = 1 AND @new_table_exists = 0,
    'RENAME TABLE `delivery_operations` TO `maa_event_logging_delivery_operations`',
    'SELECT ''DeliveryOperations table rename skipped'' AS migration_status'
);

PREPARE rename_delivery_operations_stmt FROM @rename_delivery_operations_sql;
EXECUTE rename_delivery_operations_stmt;
DEALLOCATE PREPARE rename_delivery_operations_stmt;
