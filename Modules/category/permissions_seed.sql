/*
 * Title: Permission Baseline Seed
 * Version: v1.0
 * Project: maatify/admin-control-panel
 *
 * Description:
 * - Generated from ACTUAL runtime usage
 * - Includes canonical + required standalone permissions
 * - Fully aligned with CategoryAdminPermissionMapProvider
 *
 * Rules:
 * - DO NOT modify manually
 * - Regenerate via audit pipeline only
 */

INSERT IGNORE INTO permissions (name, display_name, description)
VALUES

    -- [ CATEGORIES ]
    ('categories.list', 'List Categories', 'Allows list categories'),
    ('categories.create', 'Create Categories', 'Allows create categories'),
    ('categories.update', 'Update Categories', 'Allows update categories'),
    ('categories.set_active', 'Set active Categories', 'Allows set active categories'),
    ('categories.update_sort', 'Update sort Categories', 'Allows update sort categories'),

    -- [ CATEGORIES - SUB CATEGORIES ]
    ('categories.sub_categories.list', 'List Sub Categories', 'Allows list sub categories'),

    -- [ CATEGORIES - SETTINGS ]
    ('categories.settings.list', 'List Category Settings', 'Allows list category settings'),
    ('categories.settings.upsert', 'Upsert Category Settings', 'Allows upsert category settings'),
    ('categories.settings.delete', 'Delete Category Settings', 'Allows delete category settings'),

    -- [ CATEGORIES - IMAGES ]
    ('categories.images.list', 'List Category Images', 'Allows list category images'),
    ('categories.images.upsert', 'Upsert Category Images', 'Allows upsert category images'),
    ('categories.images.delete', 'Delete Category Images', 'Allows delete category images'),

    -- [ CATEGORIES - TRANSLATIONS ]
    ('categories.translations.list', 'List Category Translations', 'Allows list category translations'),
    ('categories.translations.upsert', 'Upsert Category Translations', 'Allows upsert category translations'),
    ('categories.translations.delete', 'Delete Category Translations', 'Allows delete category translations');

