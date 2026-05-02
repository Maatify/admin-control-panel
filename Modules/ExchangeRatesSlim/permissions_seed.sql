/*
 * Title: Permission Baseline Seed - Exchange Rates
 * Version: v1.2 (Post-Audit Stabilization)
 * Project: maatify/admin-control-panel
 *
 * Description:
 * - Generated from ACTUAL runtime usage (Audit Logs)
 * - Includes canonical + required standalone permissions
 * - Fully aligned with PermissionMapperV2
 *
 * Rules:
 * - DO NOT modify manually
 * - Regenerate via audit pipeline only
 */

INSERT IGNORE INTO permissions (name, display_name, description)
VALUES

    -- [ EXCHANGE RATES PROVIDERS ]
    ('exchange_rates.providers.list', 'List Providers', 'Allows list exchange rates providers'),
    ('exchange_rates.providers.create', 'Create Providers', 'Allows create exchange rates providers'),
    ('exchange_rates.providers.update', 'Update Providers', 'Allows update exchange rates providers'),
    ('exchange_rates.providers.delete', 'Delete Providers', 'Allows delete exchange rates providers'),
    ('exchange_rates.providers.set_active', 'Set Active Providers', 'Allows set active exchange rates providers'),
    ('exchange_rates.providers.update_sort', 'Update Sort Providers', 'Allows update sort exchange rates providers'),

    -- [ EXCHANGE RATES ]
    ('exchange_rates.rates.list', 'List Rates', 'Allows list exchange rates'),
    ('exchange_rates.rates.create', 'Create Rates', 'Allows create exchange rates'),
    ('exchange_rates.rates.update', 'Update Rates', 'Allows update exchange rates'),
    ('exchange_rates.rates.delete', 'Delete Rates', 'Allows delete exchange rates'),
    ('exchange_rates.rates.history', 'View Rates History', 'Allows view exchange rates history'),
    ('exchange_rates.rates.set_active', 'Set Active Rates', 'Allows set active exchange rates'),
    ('exchange_rates.rates.update_sort', 'Update Sort Rates', 'Allows update sort exchange rates');
