/*
 * Title: Permission Baseline Seed — GeoSlim
 * Project: maatify/admin-control-panel
 *
 * Description:
 * - Generated from ACTUAL runtime usage
 * - Fully aligned with GeoAdminPermissionMapProvider
 *
 * Rules:
 * - DO NOT modify manually
 * - Regenerate via audit pipeline only
 */

INSERT IGNORE INTO permissions (name, display_name, description)
VALUES

    -- [ COUNTRIES ]
    ('geo.countries.list',                   'List Countries',                    'Allows list countries'),
    ('geo.countries.create',                 'Create Countries',                  'Allows create countries'),
    ('geo.countries.update',                 'Update Countries',                  'Allows update countries'),
    ('geo.countries.set_active',             'Set Active Countries',              'Allows set active/inactive countries'),
    ('geo.countries.update_sort',            'Update Sort Countries',             'Allows update display order of countries'),
    ('geo.countries.translations.list',      'List Country Translations',         'Allows list country translations'),
    ('geo.countries.translations.upsert',    'Upsert Country Translations',       'Allows upsert country translations'),
    ('geo.countries.translations.delete',    'Delete Country Translations',       'Allows delete country translations'),

    -- [ CITIES ]
    ('geo.cities.list',                      'List Cities',                       'Allows list cities'),
    ('geo.cities.create',                    'Create Cities',                     'Allows create cities'),
    ('geo.cities.update',                    'Update Cities',                     'Allows update cities'),
    ('geo.cities.set_active',                'Set Active Cities',                 'Allows set active/inactive cities'),
    ('geo.cities.update_sort',               'Update Sort Cities',                'Allows update display order of cities'),
    ('geo.cities.translations.list',         'List City Translations',            'Allows list city translations'),
    ('geo.cities.translations.upsert',       'Upsert City Translations',          'Allows upsert city translations'),
    ('geo.cities.translations.delete',       'Delete City Translations',          'Allows delete city translations');

