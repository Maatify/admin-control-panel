/*
 * Title: Permission Baseline Seed
 * Version: v1.2 (Post-Audit Stabilization)
 * Project: maatify/admin-control-panel
 *
 * Description:
 * - Generated from ACTUAL runtime usage
 * - Includes canonical + required standalone permissions
 * - Fully aligned with PermissionMapperV2
 *
 * Rules:
 * - DO NOT modify manually
 * - Regenerate via audit pipeline only
 */

INSERT IGNORE INTO permissions (name, display_name, description)
VALUES

    -- [ CURRENCIES ]
    ('currencies.list', 'List Currencies', 'Allows list currencies'),
    ('currencies.create', 'Create Currencies', 'Allows create currencies'),
    ('currencies.update', 'Update Currencies', 'Allows update currencies'),
    ('currencies.set_active', 'Set active Currencies', 'Allows set active currencies'),
    ('currencies.update_sort', 'Update sort Currencies', 'Allows update sort currencies'),
    ('currencies.dropdown', 'Dropdown Currencies', 'Allows dropdown currencies'),
    ('currencies.translations.list', 'List Translations Currencies', 'Allows list translations currencies'),
    ('currencies.translations.upsert', 'Upsert Translations Currencies', 'Allows upsert translations currencies'),
    ('currencies.translations.delete', 'Delete Translations Currencies', 'Allows delete translations currencies');
