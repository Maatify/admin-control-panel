-- ============================================================
--  Category Module — Migration c001
--  Run this file once against the target database.
-- ============================================================


/* ==========================================================
 * 1) maa_categories
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
