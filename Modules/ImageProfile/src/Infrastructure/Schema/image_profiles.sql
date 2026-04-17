-- =============================================================================
-- Schema: image_profiles
-- Package: maatify/image-profile  (v1 schema — updated Phase 9)
-- =============================================================================
--
-- Design principles:
--   - `id`    is the internal database identity (never exposed as a public key)
--   - `code`  is the stable business identifier (UNIQUE, used in all API calls)
--   - min_*/max_* are nullable — NULL means the corresponding rule is disabled
--   - allowed_extensions / allowed_mime_types are stored as delimited strings
--     and parsed by AllowedExtensionCollection / AllowedMimeTypeCollection
--   - `is_active` allows soft-disabling without deleting rows
--   - created_at / updated_at are managed automatically by the DB engine
--
-- Phase 9 additions:
--   - min_aspect_ratio / max_aspect_ratio  — width÷height float ratio constraints
--   - requires_transparency                — PNG/WebP-only flag
--   - preferred_format                     — advisory output format (processing layer)
--   - preferred_quality                    — advisory quality (processing layer)
--   - variants                             — JSON array of named resize variant definitions
--
-- Delimiter format for allowed_extensions and allowed_mime_types:
--   values are comma-separated (also accepts ; and | as separators)
--   example: "jpg,jpeg,png,webp"   or   "image/jpeg,image/png,image/webp"
--
-- Aspect ratio examples:
--   16:9  landscape  = 1.7778   (minAspectRatio ≥ 1.7778)
--   1:1   square     = 1.0000
--   9:16  portrait   = 0.5625   (maxAspectRatio ≤ 0.5625)
-- =============================================================================

CREATE TABLE `image_profiles`
(
    `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `code`                  VARCHAR(64)      NOT NULL                    COMMENT 'Stable business identifier used in all API calls',
    `display_name`          VARCHAR(128)     DEFAULT NULL                COMMENT 'Human-readable label for admin UIs',

    -- Dimension constraints
    `min_width`             INT UNSIGNED     DEFAULT NULL                COMMENT 'Minimum allowed image width in pixels; NULL = no restriction',
    `min_height`            INT UNSIGNED     DEFAULT NULL                COMMENT 'Minimum allowed image height in pixels; NULL = no restriction',
    `max_width`             INT UNSIGNED     DEFAULT NULL                COMMENT 'Maximum allowed image width in pixels; NULL = no restriction',
    `max_height`            INT UNSIGNED     DEFAULT NULL                COMMENT 'Maximum allowed image height in pixels; NULL = no restriction',

    -- File size constraint
    `max_size_bytes`        BIGINT UNSIGNED  DEFAULT NULL                COMMENT 'Maximum allowed file size in bytes; NULL = no restriction',

    -- Type restrictions
    `allowed_extensions`    VARCHAR(255)     DEFAULT NULL                COMMENT 'Comma-separated list of allowed extensions e.g. "jpg,png,webp"; NULL = no restriction',
    `allowed_mime_types`    TEXT             DEFAULT NULL                COMMENT 'Comma-separated list of allowed MIME types e.g. "image/jpeg,image/webp"; NULL = no restriction',

    -- Status
    `is_active`             TINYINT(1)       NOT NULL DEFAULT 1          COMMENT '1 = active (accepted by validator); 0 = inactive (rejected by validator)',
    `notes`                 TEXT             DEFAULT NULL                COMMENT 'Internal notes for administrators; not exposed to end users',

    -- Phase 9: aspect ratio constraints (width÷height)
    `min_aspect_ratio`      DECIMAL(8,4)     DEFAULT NULL                COMMENT 'Minimum width/height ratio e.g. 1.7778 for 16:9 landscape-or-wider; NULL = no restriction',
    `max_aspect_ratio`      DECIMAL(8,4)     DEFAULT NULL                COMMENT 'Maximum width/height ratio e.g. 1.0000 for square-or-portrait; NULL = no restriction',

    -- Phase 9: transparency requirement
    `requires_transparency` TINYINT(1)       NOT NULL DEFAULT 0          COMMENT '1 = only PNG/WebP accepted (alpha-channel formats); 0 = no restriction',

    -- Phase 9: advisory processing hints (not enforced by validator)
    `preferred_format`      VARCHAR(10)      DEFAULT NULL                COMMENT 'Advisory output format for the processing layer (jpg|png|webp|gif); NULL = keep source',
    `preferred_quality`     TINYINT UNSIGNED DEFAULT NULL                COMMENT 'Advisory quality 1-100 for the processing layer; NULL = processor default',

    -- Phase 9: named variant definitions (JSON array)
    `variants`              JSON             DEFAULT NULL                COMMENT 'JSON array of named resize variant definitions; NULL = no variants defined',

    `created_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_image_profiles_code` (`code`),
    KEY `idx_image_profiles_is_active` (`is_active`)

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Reusable image validation profiles — maatify/image-profile v1';

-- =============================================================================
-- Migration: add Phase 9 columns to an existing v0.x table
-- =============================================================================
-- Run this block if upgrading from a pre-Phase-9 schema:
--
-- ALTER TABLE `image_profiles`
--     ADD COLUMN `min_aspect_ratio`      DECIMAL(8,4)     DEFAULT NULL                COMMENT 'Minimum width/height ratio; NULL = no restriction'          AFTER `notes`,
--     ADD COLUMN `max_aspect_ratio`      DECIMAL(8,4)     DEFAULT NULL                COMMENT 'Maximum width/height ratio; NULL = no restriction'          AFTER `min_aspect_ratio`,
--     ADD COLUMN `requires_transparency` TINYINT(1)       NOT NULL DEFAULT 0          COMMENT '1 = PNG/WebP only; 0 = no restriction'                     AFTER `max_aspect_ratio`,
--     ADD COLUMN `preferred_format`      VARCHAR(10)      DEFAULT NULL                COMMENT 'Advisory output format (jpg|png|webp|gif)'                  AFTER `requires_transparency`,
--     ADD COLUMN `preferred_quality`     TINYINT UNSIGNED DEFAULT NULL                COMMENT 'Advisory quality 1-100'                                    AFTER `preferred_format`,
--     ADD COLUMN `variants`              JSON             DEFAULT NULL                COMMENT 'JSON array of named resize variant definitions'             AFTER `preferred_quality`;

-- =============================================================================
-- Seed: example profiles (optional — remove if not needed in your project)
-- =============================================================================

-- INSERT INTO `image_profiles`
--     (`code`, `display_name`, `min_width`, `min_height`, `max_width`, `max_height`,
--      `max_size_bytes`, `allowed_extensions`, `allowed_mime_types`, `is_active`,
--      `min_aspect_ratio`, `max_aspect_ratio`, `requires_transparency`,
--      `preferred_format`, `preferred_quality`, `variants`)
-- VALUES
--     ('category_app_image', 'Category App Image', 200, 200, 2000, 2000, 2097152,
--      'jpg,jpeg,png,webp', 'image/jpeg,image/png,image/webp', 1,
--      NULL, NULL, 0, 'webp', 85,
--      '[{"name":"thumbnail","width":150,"height":150,"mode":"fill","quality":80,"outputFormat":"webp"}]'),
--
--     ('product_thumbnail', 'Product Thumbnail', 100, 100, 1000, 1000, 1048576,
--      'jpg,jpeg,png,webp', 'image/jpeg,image/png,image/webp', 1,
--      NULL, NULL, 0, 'webp', 85,
--      '[{"name":"thumb","width":150,"height":150,"mode":"fill","quality":80,"outputFormat":"webp"},{"name":"medium","width":600,"height":600,"mode":"fit","quality":85,"outputFormat":null}]'),
--
--     ('homepage_banner', 'Homepage Banner', 1200, 400, 3840, 2160, 5242880,
--      'jpg,jpeg,png', 'image/jpeg,image/png', 1,
--      1.0, 3.0, 0, 'jpg', 90, NULL),
--
--     ('gallery_item', 'Gallery Item', 400, 300, 4096, 4096, 10485760,
--      'jpg,jpeg,png,webp', 'image/jpeg,image/png,image/webp', 1,
--      NULL, NULL, 0, NULL, NULL, NULL);
