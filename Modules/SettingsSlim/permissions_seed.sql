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

    -- [ SETTINGS ]
    ('settings.list', 'List Settings', 'Allows list settings'),
    ('settings.view', 'View Settings', 'Allows view settings'),
    ('settings.edit', 'Edit Settings', 'Allows edit settings');
