-- =============================================================================
-- Schema: maa_website_ui_themes
-- Package: maatify/website-ui-theme
-- =============================================================================
--
-- Design principles:
--   - `id` is the internal database identity
--   - `entity_type` defines the website entity context that owns the theme
--     (example: product, category, page)
--   - `theme_file` is the Twig template file name used during frontend rendering
--   - `display_name` is the human-readable label shown in administrative dropdowns
--   - uniqueness is enforced per (`entity_type`, `theme_file`)
--     so the same theme file cannot be duplicated within the same entity context
--
-- Usage notes:
--   - this table is intended to provide controlled UI theme options
--     for administrative selection
--   - stored values are expected to be used as dropdown-backed references,
--     not as free-form user input
--   - the application layer should still apply fallback handling when no valid
--     theme is selected or when the referenced template is unavailable
-- =============================================================================

CREATE TABLE `maa_website_ui_themes`
(
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(50)  NOT NULL COMMENT 'Website entity context that uses this theme, e.g. product, category, or page',
    `theme_file`  VARCHAR(255) NOT NULL COMMENT 'Twig template file name used by the frontend renderer, e.g. product_custom.twig',
    `display_name` VARCHAR(150) NOT NULL COMMENT 'Human-readable label shown in the admin dropdown',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_maa_website_ui_themes_entity_type_theme_file` (`entity_type`, `theme_file`)

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
    COMMENT = 'Allowed website UI themes grouped by entity type — maatify/website-ui-theme';

-- =============================================================================
-- Seed: example themes (optional — keep commented if not needed in your project)
-- =============================================================================
--
-- Notes:
--   - this block is intentionally provided as an example only
--   - do not execute it blindly in production without confirming the actual
--     templates available in your project
--   - `entity_type = product` below is only a sample usage scenario
--
-- INSERT INTO `maa_website_ui_themes`
--     (`entity_type`, `theme_file`, `display_name`)
-- VALUES
--     ('product', 'athar_for_business.twig', 'Athar For Business'),
--     ('product', 'product_custom_athar.twig', 'Product Custom Athar'),
--     ('product', 'product_custom.twig', 'Product Custom'),
--     ('product', 'product_ready_made_detail.twig', 'Product Ready Made Detail'),
--     ('product', 'product_ready_made.twig', 'Product Ready Made');