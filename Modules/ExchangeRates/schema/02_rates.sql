-- ============================================================
--  Maatify ExchangeRates Module — Rates
--  Table  : maa_er_rates
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--
--  Rate convention:
--    rate = how many target units equal 1 base unit
--    e.g. base=USD  target=EGP  rate=48.7500000000
--         → 1 USD = 48.75 EGP
--
--  Precision:
--    DECIMAL(24,10) — supports crypto micro-rates (e.g. 0.0000031200)
--    and high-value pairs (e.g. KWD/USD ≈ 3.2600000000).
--    Handled as string + bcmath in PHP — never cast to float.
--
--  Uniqueness + soft delete behaviour:
--    UNIQUE KEY enforces one row per (base, target, provider_id).
--    Hard delete is NOT supported — history rows reference this id via RESTRICT FK.
--    Soft-deleted rows still occupy their unique slot.
--    A soft-deleted USD/EGP/provider pair CANNOT be recreated fresh.
--    Use restore (set deleted_at = NULL) or updateRate() on the existing row instead.
--    This is an intentional design decision to preserve rate history integrity.
--
--  display_order:
--    Scoped to provider_id — each provider has its own 1-based ordering.
--    Auto-assigned on create via ScopedOrderingManager::getNextPosition().
--    Never in CreateRateCommand or UpdateRateCommand.
--    Updated via dedicated updateDisplayOrder() service call only.
--
--  On rate change:
--    The new submitted rate value is appended to maa_er_rate_history with recorded_at.
--    The previous value is already in history from its own prior create or update.
--    This means history always reflects every value that was ever submitted.
--
--  Independence:
--    base_currency_code and target_currency_code are plain CHAR(3) ISO 4217.
--    Validation against a currencies table is the host's responsibility.
--
-- ============================================================

CREATE TABLE IF NOT EXISTS `maa_er_rates` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `provider_id`          INT UNSIGNED    NOT NULL COMMENT 'FK → maa_er_providers.id',
    `base_currency_code`   CHAR(3)         NOT NULL COMMENT 'ISO 4217 e.g. USD. Host-provided. No external FK.',
    `target_currency_code` CHAR(3)         NOT NULL COMMENT 'ISO 4217 e.g. EGP. Host-provided. No external FK.',
    `rate`                 DECIMAL(24,10)  NOT NULL COMMENT '1 base = ? target. PHP: string + bcmath, never float.',
    `is_active`            TINYINT(1)      NOT NULL DEFAULT 1  COMMENT '1=enabled, 0=disabled (not deleted)',
    `display_order`        INT UNSIGNED    NOT NULL DEFAULT 0   COMMENT 'Scoped to provider_id. Auto-assigned on create.',
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`           DATETIME            NULL COMMENT 'NULL=active, NOT NULL=soft-deleted. Soft-deleted rows cannot be recreated with same pair/provider — use restore instead.',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_maa_er_rates_pair_provider`    (`base_currency_code`, `target_currency_code`, `provider_id`),
    INDEX      `idx_maa_er_rates_provider_id`      (`provider_id`),
    INDEX      `idx_maa_er_rates_base_code`        (`base_currency_code`),
    INDEX      `idx_maa_er_rates_target_code`      (`target_currency_code`),
    INDEX      `idx_maa_er_rates_is_active`        (`is_active`),
    INDEX      `idx_maa_er_rates_display_order`    (`display_order`),
    INDEX      `idx_maa_er_rates_deleted`          (`deleted_at`),
    INDEX      `idx_maa_er_rates_pair_active`      (`base_currency_code`, `target_currency_code`, `is_active`),

    CONSTRAINT `fk_maa_er_rates_provider`
        FOREIGN KEY (`provider_id`)
            REFERENCES `maa_er_providers` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Current exchange rates per (pair, provider). History archived on every change. Soft-deleted rows cannot be recreated — restore instead.';
