-- ============================================================
--  Migration: Add description fields
--  Run this after the initial schema.sql has been applied.
-- ============================================================

-- Base (fallback) description on the category itself
ALTER TABLE `maa_categories`
    ADD COLUMN `description` TEXT NULL
        COMMENT 'Base description. Overridden per-language via maa_category_translations.description.'
        AFTER `slug`;

-- Translated description per language
ALTER TABLE `maa_category_translations`
    ADD COLUMN `description` TEXT NULL COMMENT 'Localised category description (optional)'
        AFTER `name`;
