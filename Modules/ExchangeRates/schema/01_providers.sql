-- ============================================================
--  Maatify ExchangeRates Module — Providers
--  Table  : maa_er_providers
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--
--  Policies:
--    Soft delete  : deleted_at DATETIME NULL
--                   NULL = active | NOT NULL = soft-deleted
--    Status toggle: is_active TINYINT(1)
--                   1 = enabled | 0 = disabled (soft, not deleted)
--    Hard delete  : not supported — use soft delete only
--    Unique key   : code — immutable after creation
--
--  display_order:
--    Determines provider priority when customer API is called
--    with providerId = null. Lower value = higher priority.
--    Auto-assigned on create via ScopedOrderingManager
--    (global scope — no scoping column for providers).
--    Updated via dedicated updateDisplayOrder() service call only.
--
--  Independence:
--    No FK constraints on any external (host) table.
--    This table is self-contained within the ExchangeRates module.
-- ============================================================

CREATE TABLE IF NOT EXISTS `maa_er_providers` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)  NOT NULL COMMENT 'Human-readable label e.g. "European Central Bank"',
    `code`          VARCHAR(50)   NOT NULL COMMENT 'Internal key e.g. ECB, FIXER, MANUAL — uppercase, immutable after creation',
    `description`   TEXT              NULL COMMENT 'Optional notes about this provider and its data source',
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1  COMMENT '1=enabled, 0=disabled (not deleted)',
    `display_order` INT UNSIGNED  NOT NULL DEFAULT 0  COMMENT 'Provider priority for customer API null-provider queries. Lower = higher priority. Auto-assigned on create.',
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME          NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    DATETIME          NULL COMMENT 'NULL=active, NOT NULL=soft-deleted',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_maa_er_providers_code`          (`code`),
    INDEX       `idx_maa_er_providers_is_active`    (`is_active`),
    INDEX       `idx_maa_er_providers_display_order`(`display_order`),
    INDEX       `idx_maa_er_providers_deleted`      (`deleted_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Exchange rate data sources. Standalone — no FK to any host module.';
