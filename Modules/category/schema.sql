-- ============================================================
--  Maatify Category Module — Schema
--  Includes: maa_categories, maa_category_settings, maa_category_images, maa_category_translations
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--  Run this file after the base kernel schema.
--  No external table dependencies — fully standalone.
-- ============================================================


/* ==========================================================
 * CATEGORY MODULE
 * ----------------------------------------------------------
 * Purpose:
 * - Manage hierarchical categories (root + sub-categories)
 * - Support UI configuration per category via key-value settings
 * - Support is_active toggling and display ordering per level
 *
 * Design Principles:
 * - Self-referencing parent_id for hierarchy (max depth = 2, enforced by Service)
 * - display_order is scoped per level (root level and per sub-category group)
 * - slug must be globally unique across all categories
 * - Settings are key-value pairs, one value per key per category
 * - No translation table — translations are out of scope for this module
 * - Safe for standalone extraction as a reusable library
 * ========================================================== */


/* ==========================================================
 * 1) maa_categories
 * ----------------------------------------------------------
 * Stores both root categories (parent_id IS NULL) and
 * sub-categories (parent_id = some root category id).
 *
 * Depth Rule (enforced at Service layer):
 *   depth 0 → root category    (parent_id IS NULL)
 *   depth 1 → sub-category     (parent_id points to a depth-0 row)
 *   depth 2 → NOT ALLOWED      (CategoryDepthExceededException)
 *
 * FK Rule: ON DELETE RESTRICT
 *   A root category that has sub-categories cannot be deleted
 *   until all its sub-categories are removed first.
 *   This is a safety net enforced at the DB level.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `maa_categories` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `parent_id`     INT UNSIGNED        NULL COMMENT 'NULL = root category. FK to self.',
    `name`          VARCHAR(100)    NOT NULL COMMENT 'Base display name',
    `slug`          VARCHAR(100)    NOT NULL COMMENT 'URL-safe unique identifier',
    `description`   TEXT                NULL COMMENT 'Base description. Overridden per-language via maa_category_translations.description.',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `display_order` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Scoped per parent level',
    `notes`         TEXT                NULL COMMENT 'Admin-only internal memo. Never exposed to public endpoints.',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY  `uq_maa_categories_slug`          (`slug`),
    INDEX       `idx_maa_categories_parent_id`    (`parent_id`),
    INDEX       `idx_maa_categories_is_active`    (`is_active`),
    INDEX       `idx_maa_categories_display_order`(`display_order`),

    CONSTRAINT `fk_maa_categories_parent`
        FOREIGN KEY (`parent_id`)
            REFERENCES `maa_categories` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Hierarchical categories. parent_id=NULL means root. Max depth=2 enforced at Service layer.';


/* ==========================================================
 * 2) maa_category_settings
 * ----------------------------------------------------------
 * Dynamic key-value UI configuration per category.
 *
 * Examples of keys:
 *   custom_css    → raw CSS string
 *   layout_type   → "grid" | "list" | "carousel"
 *   icon_url      → "/assets/icons/mobile.svg"
 *
 * Design:
 *   - No fixed columns — any key is valid
 *   - UNIQUE(category_id, key) ensures one value per key per category
 *   - ON DELETE CASCADE: when a category is deleted, its settings go too
 *   - Callers use upsert semantics (INSERT ... ON DUPLICATE KEY UPDATE)
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `maa_category_settings` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `key`         VARCHAR(100) NOT NULL COMMENT 'Setting key, e.g. layout_type',
    `value`       TEXT         NOT NULL COMMENT 'Setting value',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY  `uq_maa_category_settings_pair`    (`category_id`, `key`),
    INDEX       `idx_maa_category_settings_cat_id` (`category_id`),

    CONSTRAINT `fk_maa_category_settings_category`
        FOREIGN KEY (`category_id`)
            REFERENCES `maa_categories` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Dynamic key-value UI settings per category. Upsert semantics. Cascades on category delete.';


/* ==========================================================
 * 3) maa_category_images
 * ----------------------------------------------------------
 * Per-language image paths for each of the four image slots.
 *
 * Image types:
 *   image         → default / general-purpose image
 *   mobile_image  → optimised for mobile renderers
 *   api_image     → served by the consumer API (mobile app)
 *   website_image → displayed on the public-facing website
 *
 * Design:
 *   - UNIQUE(category_id, image_type, language_id) — one path per slot per language
 *   - ON DELETE CASCADE: when a category is deleted, its images go too
 *   - language_id FK enforces that only known languages can be assigned
 *   - Callers use upsert semantics (INSERT … ON DUPLICATE KEY UPDATE)
 *   - path stores the URL / relative file path — upload handled externally
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `maa_category_images` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED    NOT NULL,
    `image_type`  ENUM('image','mobile_image','api_image','website_image') NOT NULL
                  COMMENT 'One of four supported image slots',
    `language_id` INT UNSIGNED    NOT NULL COMMENT 'FK to languages.id',
    `path`        VARCHAR(500)    NOT NULL COMMENT 'Stored URL or relative file path',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY  `uq_maa_category_image_slot`        (`category_id`, `image_type`, `language_id`),
    INDEX       `idx_maa_category_images_cat_id`    (`category_id`),
    INDEX       `idx_maa_category_images_lang_id`   (`language_id`),

    CONSTRAINT `fk_maa_category_images_category`
        FOREIGN KEY (`category_id`)
            REFERENCES `maa_categories` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,

    CONSTRAINT `fk_maa_category_images_language`
        FOREIGN KEY (`language_id`)
            REFERENCES `languages` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Per-language image paths per category slot. Upsert semantics. Cascades on category delete.';


/* ==========================================================
 * 4) maa_category_translations
 * ----------------------------------------------------------
 * Stores the localised name of a category per language.
 *
 * What IS translated   : name  (e.g. "Electronics" → "إلكترونيات")
 * What is NOT translated: slug (URL-safe; must stay stable)
 *
 * Fallback policy — COALESCE:
 *   When no row exists for (category_id, language_id) the query
 *   automatically falls back to maa_categories.name.
 *   The caller always receives a non-null string — no null-checks needed.
 *
 * Listing policy — LEFT JOIN languages:
 *   listTranslationsForCategoryPaginated() joins the languages table so
 *   every active language is shown, including those without a translation.
 *   translatedName is null for untranslated languages.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `maa_category_translations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `language_id` INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL COMMENT 'Localised category name',
    `description` TEXT             NULL COMMENT 'Localised category description (optional)',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_maa_category_translations_pair`   (`category_id`, `language_id`),
    INDEX       `idx_maa_category_translations_lang` (`language_id`),

    CONSTRAINT `fk_maa_ctr_category`
        FOREIGN KEY (`category_id`) REFERENCES `maa_categories` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_maa_ctr_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Localised category names — COALESCE fallback to base name when missing.';
