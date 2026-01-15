SET FOREIGN_KEY_CHECKS=0;

-- Dependent / leaf tables
DROP TABLE IF EXISTS admin_direct_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS admin_roles;
DROP TABLE IF EXISTS step_up_grants;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_passwords;
DROP TABLE IF EXISTS admin_emails;
DROP TABLE IF EXISTS admin_remember_me_tokens;

DROP TABLE IF EXISTS verification_codes;
DROP TABLE IF EXISTS admin_notification_preferences;
DROP TABLE IF EXISTS admin_notification_channels;
DROP TABLE IF EXISTS admin_notifications;
DROP TABLE IF EXISTS failed_notifications;

DROP TABLE IF EXISTS audit_outbox;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS security_events;
DROP TABLE IF EXISTS telemetry_traces;
DROP TABLE IF EXISTS activity_logs;

-- Delivery queues (independent, no FK but semantically leaf)
DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS telegram_queue;

-- Auth / RBAC roots
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;

-- System-level ownership
DROP TABLE IF EXISTS system_ownership;

-- Root entity
DROP TABLE IF EXISTS admins;

-- System state (independent)
DROP TABLE IF EXISTS system_state;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_ownership (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_so_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    email_blind_index CHAR(64) NOT NULL,
    email_ciphertext VARBINARY(512) NOT NULL,
    email_iv VARBINARY(16) NOT NULL,
    email_tag VARBINARY(16) NOT NULL,
    email_key_id VARCHAR(64) NOT NULL,
    verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_email_blind_index (email_blind_index),
    CONSTRAINT fk_ae_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_passwords (
    admin_id INT PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    pepper_id VARCHAR(16) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ap_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_sessions (
    session_id VARCHAR(64) PRIMARY KEY,
    admin_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_revoked TINYINT(1) DEFAULT 0 NOT NULL,
    CONSTRAINT fk_as_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_roles (
    admin_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (admin_id, role_id),
    CONSTRAINT fk_ar_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    CONSTRAINT fk_ar_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission_id FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Identity-Based Verification Codes
-- NOTE: Application logic MUST enforce only one 'active' code per (identity_type, identity_id, purpose).
-- Database constraints do not strictly enforce this to allow history retention (used/expired codes).
CREATE TABLE verification_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identity_type ENUM('admin', 'user', 'customer') NOT NULL,
    identity_id VARCHAR(64) NOT NULL,
    purpose VARCHAR(64) NOT NULL,
    code_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    max_attempts INT UNSIGNED NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','used','expired','revoked') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL,
    UNIQUE INDEX uniq_verification_code_hash (code_hash),
    INDEX idx_active_lookup (identity_type, identity_id, purpose, status),
    INDEX idx_status_expiry (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Channels
CREATE TABLE admin_notification_channels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    channel ENUM('email','telegram') NOT NULL,
    identifier VARCHAR(255) NOT NULL,
    status ENUM('pending','active','revoked') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    UNIQUE KEY uq_admin_channel (admin_id, channel),
    CONSTRAINT fk_anc_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    notification_type VARCHAR(64) NOT NULL,
    channel_type VARCHAR(32) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_type_channel (admin_id, notification_type, channel_type),
    CONSTRAINT fk_anp_admin_id FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    notification_type VARCHAR(64) NOT NULL,
    channel_type VARCHAR(32) NOT NULL,
    intent_id VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    CONSTRAINT fk_an_admin_id FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel VARCHAR(50) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reason TEXT NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    last_attempt_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_failed_channel (channel),
    INDEX idx_failed_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_admin_id INT NULL,
    target_type VARCHAR(64) NOT NULL,
    target_id INT NULL,
    action VARCHAR(32) NOT NULL,
    changes JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    occurred_at DATETIME NOT NULL,
    INDEX idx_audit_actor (actor_admin_id),
    INDEX idx_audit_target (target_type, target_id),
    INDEX idx_audit_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE security_events (
                                 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                 actor_type VARCHAR(32) NOT NULL,
                                 actor_id BIGINT UNSIGNED NULL,

                                 event_type VARCHAR(100) NOT NULL,
                                 severity VARCHAR(20) NOT NULL,

                                 request_id VARCHAR(64) NULL,
                                 route_name VARCHAR(255) NULL,

                                 ip_address VARCHAR(45) NULL,
                                 user_agent TEXT NULL,

                                 metadata JSON NOT NULL,

                                 occurred_at DATETIME NOT NULL,

                                 INDEX idx_security_actor (actor_type, actor_id),
                                 INDEX idx_security_event_type (event_type),
                                 INDEX idx_security_severity (severity),
                                 INDEX idx_security_request_id (request_id),
                                 INDEX idx_security_occurred_at (occurred_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_remember_me_tokens (
    selector CHAR(32) NOT NULL PRIMARY KEY,
    hashed_validator CHAR(64) NOT NULL,
    admin_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_agent_hash CHAR(64) NOT NULL,
    CONSTRAINT fk_armt_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_outbox (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  actor_id BIGINT NULL,
  action VARCHAR(128) NOT NULL,
  target_type VARCHAR(64) NOT NULL,
  target_id BIGINT NULL,
  risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
  payload JSON NOT NULL,
  correlation_id CHAR(36) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE step_up_grants (
    admin_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    scope VARCHAR(64) NOT NULL,
    risk_context_hash VARCHAR(64) NOT NULL,
    issued_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    single_use TINYINT(1) NOT NULL DEFAULT 0,
    context_snapshot JSON NULL,
    PRIMARY KEY (admin_id, session_id, scope),
    CONSTRAINT fk_sug_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    CONSTRAINT fk_sug_session_id FOREIGN KEY (session_id) REFERENCES admin_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_direct_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    permission_id INT NOT NULL,
    is_allowed TINYINT(1) NOT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    CONSTRAINT fk_adp_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    CONSTRAINT fk_adp_permission_id FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_adp_lookup (admin_id, permission_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_state (
    state_key VARCHAR(64) PRIMARY KEY,
    state_value VARCHAR(64) NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_queue` (
                               `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    /* ===== Entity Binding (Domain Trace) ===== */
                               `entity_type` VARCHAR(32) NOT NULL COMMENT 'admin | user | system | external',
                               `entity_id` VARCHAR(64) DEFAULT NULL COMMENT 'Domain entity identifier (string or int casted)',

    /* ===== Recipient (Encrypted) ===== */
                               `recipient_encrypted` VARBINARY(512) NOT NULL,
                               `recipient_iv` VARBINARY(16) NOT NULL,
                               `recipient_tag` VARBINARY(16) NOT NULL,
                               `recipient_key_id` VARCHAR(64) NOT NULL,

    /* ===== Payload (Encrypted rendered output) ===== */
                               `payload_encrypted` LONGBLOB NOT NULL,
                               `payload_iv` VARBINARY(16) NOT NULL,
                               `payload_tag` VARBINARY(16) NOT NULL,
                               `payload_key_id` VARCHAR(64) NOT NULL,

    /* ===== Template Binding ===== */
                               `template_key` VARCHAR(100) NOT NULL,
                               `language` VARCHAR(5) NOT NULL,

    /* ===== Delivery Metadata ===== */
                               `sender_type` TINYINT UNSIGNED NOT NULL COMMENT 'system-defined sender profile',
                               `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'lower = higher priority',

    /* ===== Queue State ===== */
                               `status` ENUM('pending','processing','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                               `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                               `last_error` VARCHAR(128) NOT NULL DEFAULT '',

    /* ===== Scheduling ===== */
                               `scheduled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               `sent_at` DATETIME DEFAULT NULL,

    /* ===== Timestamps ===== */
                               `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                               PRIMARY KEY (`id`),

    /* ===== Indexes ===== */
                               KEY `idx_email_queue_status_schedule` (`status`, `scheduled_at`),
                               KEY `idx_email_queue_entity` (`entity_type`, `entity_id`),
                               KEY `idx_email_queue_template` (`template_key`),
                               KEY `idx_email_queue_language` (`language`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted async email delivery queue';


CREATE TABLE `telegram_queue` (
                                  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    /* ===== Correlation ===== */
                                  `source` VARCHAR(32) NOT NULL COMMENT 'notification | system | manual',
                                  `source_ref` VARCHAR(64) DEFAULT NULL COMMENT 'intent_id or external ref',

    /* ===== Entity Binding ===== */
                                  `entity_type` VARCHAR(32) NOT NULL COMMENT 'admin | system',
                                  `entity_id` VARCHAR(64) NOT NULL COMMENT 'Admin ID (string-safe)',

    /* ===== Telegram Recipient (Encrypted) ===== */
                                  `chat_id_encrypted` VARBINARY(255) NOT NULL,
                                  `chat_id_iv` VARBINARY(16) NOT NULL,
                                  `chat_id_tag` VARBINARY(16) NOT NULL,
                                  `chat_id_key_id` VARCHAR(64) NOT NULL,

    /* ===== Message Payload (Encrypted) ===== */
                                  `message_encrypted` LONGBLOB NOT NULL,
                                  `message_iv` VARBINARY(16) NOT NULL,
                                  `message_tag` VARBINARY(16) NOT NULL,
                                  `message_key_id` VARCHAR(64) NOT NULL,

    /* ===== Telegram Options ===== */
                                  `parse_mode` ENUM('HTML','MarkdownV2','Plain') NOT NULL DEFAULT 'HTML',
                                  `disable_preview` BOOLEAN NOT NULL DEFAULT 1,

    /* ===== Delivery State ===== */
                                  `status` ENUM('pending','processing','sent','failed','skipped')
                                      NOT NULL DEFAULT 'pending',
                                  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                                  `last_error` VARCHAR(128) NOT NULL DEFAULT '',

    /* ===== Scheduling ===== */
                                  `scheduled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `sent_at` DATETIME DEFAULT NULL,

    /* ===== Timestamps ===== */
                                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `updated_at` TIMESTAMP NOT NULL
                                      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                  PRIMARY KEY (`id`),

                                  KEY `idx_tg_status_schedule` (`status`, `scheduled_at`),
                                  KEY `idx_tg_entity` (`entity_type`, `entity_id`),
                                  KEY `idx_tg_source` (`source`, `source_ref`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted Telegram async delivery queue';

CREATE TABLE telemetry_traces (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Event identity
                                  event_key VARCHAR(255) NOT NULL,
                                  severity VARCHAR(16) NOT NULL DEFAULT 'info',

    -- Request routing / correlation
                                  route_name VARCHAR(255) NULL,
                                  request_id VARCHAR(64) NULL,

    -- Actor (admin)
                                  actor_admin_id INT NULL,

    -- Request context
                                  ip_address VARCHAR(45) NULL,
                                  user_agent TEXT NULL,

    -- Extra data
                                  metadata JSON NULL,

    -- Timestamp
                                  occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    -- Indexes (high-volume)
                                  INDEX idx_telemetry_event_key (event_key),
                                  INDEX idx_telemetry_severity (severity),
                                  INDEX idx_telemetry_actor (actor_admin_id),
                                  INDEX idx_telemetry_request (request_id),
                                  INDEX idx_telemetry_route (route_name),
                                  INDEX idx_telemetry_occurred_at (occurred_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE activity_logs (
                               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Actor info
                               actor_type VARCHAR(32) NOT NULL,
                               actor_id BIGINT UNSIGNED NULL,

    -- Action semantics
                               action VARCHAR(128) NOT NULL,

    -- Target entity
                               entity_type VARCHAR(64) NULL,
                               entity_id BIGINT UNSIGNED NULL,

    -- Extra context
                               metadata JSON NULL,

    -- Request context
                               ip_address VARCHAR(45) NULL,
                               user_agent VARCHAR(255) NULL,

    -- Correlation / tracing
                               request_id VARCHAR(64) NULL,

    -- Timestamp
                               occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    -- Indexes
                               INDEX idx_actor (actor_type, actor_id),
                               INDEX idx_action (action),
                               INDEX idx_entity (entity_type, entity_id),
                               INDEX idx_occurred_at (occurred_at),
                               INDEX idx_request (request_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
