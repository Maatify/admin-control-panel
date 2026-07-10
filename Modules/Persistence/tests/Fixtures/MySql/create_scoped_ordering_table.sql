CREATE TABLE `maa_persistence_test_scoped_ordering` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_key` VARCHAR(191) NOT NULL,
  `display_order` INT NOT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  `label` VARCHAR(191) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_maa_persistence_test_scoped_ordering_scope_order` (`scope_key`, `display_order`),
  KEY `idx_maa_persistence_test_scoped_ordering_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
