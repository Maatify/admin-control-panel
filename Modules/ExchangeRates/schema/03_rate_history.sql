-- ============================================================
--  Maatify ExchangeRates Module — Rate History
--  Table  : maa_er_rate_history
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--
--  Purpose:
--    Append-only archive of every rate value ever recorded.
--    Written automatically on every maa_er_rates insert/update.
--
--  Rules:
--    - Rows are NEVER updated
--    - Rows are NEVER deleted (no soft delete, no hard delete)
--    - rate_id FK uses ON DELETE RESTRICT to protect integrity
--
--  recorded_at:
--    Set explicitly by the application — NOT DEFAULT CURRENT_TIMESTAMP.
--    This allows bulk backfill from external provider feeds
--    with the exact timestamp the provider published the rate.
--    Application must always supply a valid DATETIME string.
--
--  Denormalisation:
--    provider_id, base_currency_code, target_currency_code are
--    denormalised snapshots of the rate row at insert time.
--    This preserves history even if the parent rate is soft-deleted
--    and enables fast pair-scoped history queries without a JOIN.
--
-- ============================================================

CREATE TABLE IF NOT EXISTS `maa_er_rate_history` (
    `id`                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT  COMMENT 'BIGINT — will grow fast in high-frequency update scenarios',
    `rate_id`              INT UNSIGNED     NOT NULL COMMENT 'FK → maa_er_rates.id. RESTRICT prevents orphan history.',
    `provider_id`          INT UNSIGNED     NOT NULL COMMENT 'Denormalised from maa_er_rates at write time',
    `base_currency_code`   CHAR(3)          NOT NULL COMMENT 'Denormalised snapshot — ISO 4217',
    `target_currency_code` CHAR(3)          NOT NULL COMMENT 'Denormalised snapshot — ISO 4217',
    `rate`                 DECIMAL(24,10)   NOT NULL COMMENT 'The rate value at this point in time. PHP: string + bcmath.',
    `recorded_at`          DATETIME         NOT NULL COMMENT 'Provider publish time. Set by application — NOT auto. Supports backfill.',
    `created_at`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this row was written to DB',

    PRIMARY KEY (`id`),
    INDEX `idx_maa_er_rh_rate_id`        (`rate_id`),
    INDEX `idx_maa_er_rh_provider_id`    (`provider_id`),
    INDEX `idx_maa_er_rh_base_code`      (`base_currency_code`),
    INDEX `idx_maa_er_rh_target_code`    (`target_currency_code`),
    INDEX `idx_maa_er_rh_recorded_at`    (`recorded_at`),
    INDEX `idx_maa_er_rh_pair_recorded`  (`base_currency_code`, `target_currency_code`, `recorded_at`),

    CONSTRAINT `fk_maa_er_rh_rate`
        FOREIGN KEY (`rate_id`)
            REFERENCES `maa_er_rates` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE,

    CONSTRAINT `fk_maa_er_rh_provider`
        FOREIGN KEY (`provider_id`)
            REFERENCES `maa_er_providers` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only rate archive. Never modified after insert. Supports point-in-time lookup and backfill.';
