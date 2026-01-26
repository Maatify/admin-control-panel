SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */

DROP TABLE IF EXISTS admin_direct_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS admin_roles;
DROP TABLE IF EXISTS step_up_grants;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_passwords;
DROP TABLE IF EXISTS admin_emails;
DROP TABLE IF EXISTS admin_remember_me_tokens;
DROP TABLE IF EXISTS admin_totp_secrets;

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

DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS telegram_queue;

DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS system_ownership;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS system_state;

SET FOREIGN_KEY_CHECKS=1;

/* ===========================
 * ROOT TABLES
 * =========================== */

CREATE TABLE admins (
                        id INT AUTO_INCREMENT PRIMARY KEY,

                        display_name VARCHAR(100) NULL,

                        status ENUM('ACTIVE','SUSPENDED','DISABLED')
                                                  NOT NULL
                            DEFAULT 'ACTIVE',

                        created_at DATETIME
                                                  NOT NULL
                            DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE system_ownership (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  admin_id INT NOT NULL UNIQUE,
                                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  CONSTRAINT fk_so_admin_id
                                      FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================
 * AUTH / IDENTITY
 * =========================== */

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

                              email_unique_guard CHAR(64)
                                  GENERATED ALWAYS AS (
                                      IF(verification_status != 'replaced', email_blind_index, NULL)
                                      ) STORED,

                              UNIQUE KEY uq_email_active_only (email_unique_guard),

                              CONSTRAINT fk_ae_admin_id
                                  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

CREATE TABLE admin_passwords (
                                 admin_id INT PRIMARY KEY,
                                 password_hash VARCHAR(255) NOT NULL,
                                 pepper_id VARCHAR(16) NOT NULL,
                                 must_change_password TINYINT(1) NOT NULL DEFAULT 0,
                                 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                 CONSTRAINT fk_ap_admin_id
                                     FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_sessions (
                                session_id VARCHAR(64) PRIMARY KEY,
                                admin_id INT NOT NULL,

                                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                expires_at DATETIME NOT NULL,
                                is_revoked TINYINT(1) NOT NULL DEFAULT 0,

    -- Pending TOTP Enrollment (Session-bound)
                                pending_totp_seed_ciphertext VARBINARY(512) NULL
                                    COMMENT 'Encrypted pending TOTP seed (CryptoContext::TOTP_SEED_V1)',
                                pending_totp_seed_iv VARBINARY(16) NULL,
                                pending_totp_seed_tag VARBINARY(16) NULL,
                                pending_totp_seed_key_id VARCHAR(64) NULL,
                                pending_totp_issued_at DATETIME NULL
                                    COMMENT 'When pending TOTP enrollment was initiated',

                                CONSTRAINT fk_as_admin_id
                                    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Admin authenticated sessions (auth_token-bound) with optional pending TOTP enrollment state';


CREATE TABLE admin_totp_secrets (
                                    admin_id INT NOT NULL,
                                    seed_ciphertext VARBINARY(512) NOT NULL,
                                    seed_iv VARBINARY(16) NOT NULL,
                                    seed_tag VARBINARY(16) NOT NULL,
                                    seed_key_id VARCHAR(64) NOT NULL,
                                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    rotated_at TIMESTAMP NULL DEFAULT NULL,
                                    PRIMARY KEY (admin_id),
                                    CONSTRAINT fk_admin_totp_admin
                                        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted TOTP seeds for admins (CryptoContext::TOTP_SEED_V1)';

/* ===========================
 * RBAC
 * =========================== */

-- IMPORTANT:
-- roles.name and permissions.name are TECHNICAL KEYS ONLY
-- They must NEVER be translated or shown directly to end users.
-- display_name / description are UI-only fields.
CREATE TABLE roles (
                       id INT AUTO_INCREMENT PRIMARY KEY,

    -- TECHNICAL role key (language-agnostic)
                       name VARCHAR(64) NOT NULL UNIQUE,

    -- Lifecycle status (reserved for future enforcement)
    -- 1 = active (default)
    -- 0 = inactive (role exists but should be ignored by authorization logic)
                       is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- UI display fields (NOT used in authorization logic)
                       display_name VARCHAR(128) NULL,
                       description VARCHAR(255) NULL,

                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



CREATE TABLE permissions (
                             id INT AUTO_INCREMENT PRIMARY KEY,

    -- TECHNICAL permission key (used by middleware / guards)
                             name VARCHAR(64) NOT NULL UNIQUE,

    -- UI display fields (can be localized later)
                             display_name VARCHAR(128) NULL,
                             description VARCHAR(255) NULL,

                             created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE admin_roles (
                             admin_id INT NOT NULL,
                             role_id INT NOT NULL,
                             PRIMARY KEY (admin_id, role_id),
                             CONSTRAINT fk_ar_admin_id
                                 FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                             CONSTRAINT fk_ar_role_id
                                 FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
                                  role_id INT NOT NULL,
                                  permission_id INT NOT NULL,
                                  PRIMARY KEY (role_id, permission_id),
                                  CONSTRAINT fk_rp_role_id
                                      FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                                  CONSTRAINT fk_rp_permission_id
                                      FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_direct_permissions (
                                          id INT AUTO_INCREMENT PRIMARY KEY,
                                          admin_id INT NOT NULL,
                                          permission_id INT NOT NULL,
                                          is_allowed TINYINT(1) NOT NULL,
                                          granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          expires_at DATETIME NULL,
                                          CONSTRAINT fk_adp_admin_id
                                              FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                                          CONSTRAINT fk_adp_permission_id
                                              FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
                                          INDEX idx_adp_lookup (admin_id, permission_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================
 * STEP-UP / REMEMBER ME
 * =========================== */

CREATE TABLE admin_remember_me_tokens (
                                          selector CHAR(32) NOT NULL PRIMARY KEY,
                                          hashed_validator CHAR(64) NOT NULL,
                                          admin_id INT NOT NULL,
                                          expires_at DATETIME NOT NULL,
                                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                          user_agent_hash CHAR(64) NOT NULL,
                                          CONSTRAINT fk_armt_admin_id
                                              FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
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
                                CONSTRAINT fk_sug_admin_id
                                    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                                CONSTRAINT fk_sug_session_id
                                    FOREIGN KEY (session_id) REFERENCES admin_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================
 * SYSTEM STATE
 * =========================== */

CREATE TABLE system_state (
                              state_key VARCHAR(64) PRIMARY KEY,
                              state_value VARCHAR(64) NOT NULL,
                              updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================
 * VERIFICATION / NOTIFICATIONS
 * =========================== */

CREATE TABLE verification_codes (
                                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    identity_type ENUM('admin','user','customer') NOT NULL,
                                    identity_id VARCHAR(64) NOT NULL,
                                    purpose VARCHAR(64) NOT NULL,
                                    code_hash VARCHAR(64) NOT NULL,
                                    expires_at DATETIME NOT NULL,
                                    max_attempts INT UNSIGNED NOT NULL,
                                    attempts INT UNSIGNED NOT NULL DEFAULT 0,
                                    status ENUM('active','used','expired','revoked') NOT NULL DEFAULT 'active',
                                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    used_at DATETIME NULL,
                                    UNIQUE KEY uniq_verification_code_hash (code_hash),
                                    INDEX idx_active_lookup (identity_type, identity_id, purpose, status),
                                    INDEX idx_status_expiry (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notification_channels (
                                             id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                             admin_id INT NOT NULL,
                                             channel ENUM('email','telegram') NOT NULL,
                                             identifier VARCHAR(255) NOT NULL,
                                             status ENUM('pending','active','revoked') NOT NULL DEFAULT 'pending',
                                             created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                             verified_at DATETIME NULL,
                                             UNIQUE KEY uq_admin_channel (admin_id, channel),
                                             UNIQUE KEY uq_channel_identifier (channel, identifier),
                                             CONSTRAINT fk_anc_admin_id
                                                 FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
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
                                                CONSTRAINT fk_anp_admin_id
                                                    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notifications (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     admin_id INT NOT NULL,
                                     notification_type VARCHAR(64) NOT NULL,
                                     channel_type VARCHAR(32) NOT NULL,
                                     intent_id VARCHAR(64) NULL,
                                     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     read_at DATETIME NULL,
                                     CONSTRAINT fk_an_admin_id
                                         FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
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

/* ===========================
 * AUDIT / LOGGING (CANONICAL)
 * =========================== */

CREATE TABLE audit_outbox (
                              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                              actor_type VARCHAR(32) NOT NULL,
                              actor_id BIGINT NULL,

                              action VARCHAR(128) NOT NULL,
                              target_type VARCHAR(64) NOT NULL,
                              target_id BIGINT NULL,

                              risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
                              payload JSON NOT NULL,

                              correlation_id CHAR(36) NOT NULL,
                              created_at DATETIME(6) NOT NULL,

                              INDEX idx_audit_actor (actor_type, actor_id),
                              INDEX idx_audit_target (target_type, target_id),
                              INDEX idx_audit_correlation (correlation_id),
                              INDEX idx_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                            actor_type VARCHAR(32) NOT NULL,
                            actor_id BIGINT NULL,

                            action VARCHAR(128) NOT NULL,
                            target_type VARCHAR(64) NOT NULL,
                            target_id BIGINT NULL,

                            changes JSON NULL,

                            ip_address VARCHAR(45) NULL,
                            user_agent TEXT NULL,

                            correlation_id CHAR(36) NULL,
                            occurred_at DATETIME(6) NOT NULL,

                            INDEX idx_audit_actor (actor_type, actor_id),
                            INDEX idx_audit_target (target_type, target_id),
                            INDEX idx_audit_correlation (correlation_id),
                            INDEX idx_audit_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Materialized audit log. MUST be written ONLY by audit outbox consumer';

CREATE TABLE security_events (
                                 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                 actor_type VARCHAR(32) NOT NULL,
                                 actor_id BIGINT NULL,

                                 event_type VARCHAR(100) NOT NULL,
                                 severity ENUM('INFO','WARNING','ERROR','CRITICAL') NOT NULL,

                                 correlation_id CHAR(36) NULL,
                                 request_id VARCHAR(64) NULL,
                                 route_name VARCHAR(255) NULL,

                                 ip_address VARCHAR(45) NULL,
                                 user_agent TEXT NULL,

                                 metadata JSON NOT NULL,
                                 occurred_at DATETIME(6) NOT NULL,

                                 INDEX idx_security_actor (actor_type, actor_id),
                                 INDEX idx_security_event_type (event_type),
                                 INDEX idx_security_severity (severity),
                                 INDEX idx_security_correlation (correlation_id),
                                 INDEX idx_security_request_id (request_id),
                                 INDEX idx_security_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
                               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                               actor_type VARCHAR(32) NOT NULL,
                               actor_id BIGINT NULL,

                               action VARCHAR(128) NOT NULL,

                               entity_type VARCHAR(64) NULL,
                               entity_id BIGINT NULL,

                               metadata JSON NOT NULL,

                               correlation_id CHAR(36) NULL,
                               request_id VARCHAR(64) NULL,
                               route_name VARCHAR(255) NULL,

                               ip_address VARCHAR(45) NULL,
                               user_agent TEXT NULL,

                               occurred_at DATETIME(6) NOT NULL,

                               INDEX idx_activity_actor (actor_type, actor_id),
                               INDEX idx_activity_action (action),
                               INDEX idx_activity_entity (entity_type, entity_id),
                               INDEX idx_activity_correlation (correlation_id),
                               INDEX idx_activity_request (request_id),
                               INDEX idx_activity_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Operational actions only. Views/reads are NOT allowed';

CREATE TABLE telemetry_traces (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                  event_key VARCHAR(255) NOT NULL,
                                  severity ENUM('info','warning','error','critical') NOT NULL DEFAULT 'info',

                                  actor_type VARCHAR(32) NOT NULL,
                                  actor_id BIGINT NULL,

                                  correlation_id CHAR(36) NULL,
                                  request_id VARCHAR(64) NULL,
                                  route_name VARCHAR(255) NULL,

                                  ip_address VARCHAR(45) NULL,
                                  user_agent TEXT NULL,

                                  metadata JSON NULL,
                                  occurred_at DATETIME(6) NOT NULL,

                                  INDEX idx_telemetry_actor (actor_type, actor_id),
                                  INDEX idx_telemetry_event_key (event_key),
                                  INDEX idx_telemetry_severity (severity),
                                  INDEX idx_telemetry_correlation (correlation_id),
                                  INDEX idx_telemetry_request (request_id),
                                  INDEX idx_telemetry_route (route_name),
                                  INDEX idx_telemetry_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================
 * ASYNC QUEUES
 * =========================== */

CREATE TABLE email_queue (
                             id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                             entity_type VARCHAR(32) NOT NULL,
                             entity_id VARCHAR(64) DEFAULT NULL,
                             recipient_encrypted VARBINARY(512) NOT NULL,
                             recipient_iv VARBINARY(16) NOT NULL,
                             recipient_tag VARBINARY(16) NOT NULL,
                             recipient_key_id VARCHAR(64) NOT NULL,
                             payload_encrypted LONGBLOB NOT NULL,
                             payload_iv VARBINARY(16) NOT NULL,
                             payload_tag VARBINARY(16) NOT NULL,
                             payload_key_id VARCHAR(64) NOT NULL,
                             template_key VARCHAR(100) NOT NULL,
                             language VARCHAR(5) NOT NULL,
                             sender_type TINYINT UNSIGNED NOT NULL,
                             priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
                             status ENUM('pending','processing','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                             attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                             last_error VARCHAR(128) NOT NULL DEFAULT '',
                             scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             sent_at DATETIME DEFAULT NULL,
                             created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                             PRIMARY KEY (id),
                             KEY idx_email_queue_status_schedule (status, scheduled_at),
                             KEY idx_email_queue_entity (entity_type, entity_id),
                             KEY idx_email_queue_template (template_key),
                             KEY idx_email_queue_language (language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted async email delivery queue';

CREATE TABLE telegram_queue (
                                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                source VARCHAR(32) NOT NULL,
                                source_ref VARCHAR(64) DEFAULT NULL,
                                entity_type VARCHAR(32) NOT NULL,
                                entity_id VARCHAR(64) NOT NULL,
                                chat_id_encrypted VARBINARY(255) NOT NULL,
                                chat_id_iv VARBINARY(16) NOT NULL,
                                chat_id_tag VARBINARY(16) NOT NULL,
                                chat_id_key_id VARCHAR(64) NOT NULL,
                                message_encrypted LONGBLOB NOT NULL,
                                message_iv VARBINARY(16) NOT NULL,
                                message_tag VARBINARY(16) NOT NULL,
                                message_key_id VARCHAR(64) NOT NULL,
                                parse_mode ENUM('HTML','MarkdownV2','Plain') NOT NULL DEFAULT 'HTML',
                                disable_preview BOOLEAN NOT NULL DEFAULT 1,
                                status ENUM('pending','processing','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                                last_error VARCHAR(128) NOT NULL DEFAULT '',
                                scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                sent_at DATETIME DEFAULT NULL,
                                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                PRIMARY KEY (id),
                                KEY idx_tg_status_schedule (status, scheduled_at),
                                KEY idx_tg_entity (entity_type, entity_id),
                                KEY idx_tg_source (source, source_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted Telegram async delivery queue';

/* ===========================
 * LOGGING DOMAINS (CANONICAL) - FIRST-TIME BASELINE (NO ARCHIVE)
 * Compatibility: MySQL 5.7+ (No MySQL 8-only features)
 * Notes:
 * - All severities / risk levels are VARCHAR for max portability.
 * - Cursor index (occurred_at, id) exists on all tables to enable stable paging & future archiving.
 * - JSON columns MUST NOT store secrets (tokens/OTPs/passwords).
 * =========================== */


-- ==========================================================
-- 1) Authoritative Audit (COMPLIANCE-GRADE / FAIL-CLOSED)
-- ----------------------------------------------------------
-- Source of truth is the OUTBOX (transactional).
-- The LOG is a materialized view written ONLY by a consumer.
-- ==========================================================

CREATE TABLE authoritative_audit_outbox (
                                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID string for idempotency / tracing across systems (portable, no BINARY dependency)
                                            event_id CHAR(36) NOT NULL,

    -- Who caused the event (ADMIN / SYSTEM / SERVICE ...)
                                            actor_type VARCHAR(32) NOT NULL,
                                            actor_id BIGINT NULL,

    -- What happened (authoritative state change)
                                            action VARCHAR(128) NOT NULL,

    -- What was affected
                                            target_type VARCHAR(64) NOT NULL,
                                            target_id BIGINT NULL,

    -- Risk level (portable VARCHAR; values should be controlled at app-level)
    -- Recommended values: LOW|MEDIUM|HIGH|CRITICAL
                                            risk_level VARCHAR(16) NOT NULL,

    -- Authoritative event payload (MUST NOT contain secrets)
                                            payload JSON NOT NULL,

    -- Correlation between different log domains and request pipeline
                                            correlation_id CHAR(36) NOT NULL,

    -- Outbox enqueue timestamp (transactional)
                                            created_at DATETIME(6) NOT NULL,

    -- Idempotency: same event MUST NOT be written twice
                                            UNIQUE KEY uq_auth_audit_outbox_event_id (event_id),

    -- Cursor index for stable paging / future batch processing (no partitioning assumptions)
                                            INDEX idx_auth_audit_outbox_time (created_at, id),

    -- Common investigations
                                            INDEX idx_auth_audit_outbox_actor_time (actor_type, actor_id, created_at),
                                            INDEX idx_auth_audit_outbox_target_time (target_type, target_id, created_at),
                                            INDEX idx_auth_audit_outbox_correlation_time (correlation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Authoritative audit outbox (source of truth). Transactional + fail-closed. Written ONLY inside business transactions. Payload MUST NOT contain secrets (tokens/OTPs/passwords).';


CREATE TABLE authoritative_audit_log (
                                         id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Same event_id from outbox (idempotency & cross-reference)
                                         event_id CHAR(36) NOT NULL,

    -- Who caused the event
                                         actor_type VARCHAR(32) NOT NULL,
                                         actor_id BIGINT NULL,

    -- What happened (authoritative state change)
                                         action VARCHAR(128) NOT NULL,

    -- What was affected
                                         target_type VARCHAR(64) NOT NULL,
                                         target_id BIGINT NULL,

    -- Optional structured changes (MUST remain minimal; never store secrets)
                                         changes JSON NULL,

    -- Request context
                                         ip_address VARCHAR(45) NULL,
                                         user_agent VARCHAR(512) NULL,

    -- Correlation to other logs / traces
                                         correlation_id CHAR(36) NULL,

    -- Materialized timestamp (usually equals business occurred time)
                                         occurred_at DATETIME(6) NOT NULL,

                                         UNIQUE KEY uq_auth_audit_log_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                         INDEX idx_auth_audit_log_time (occurred_at, id),

    -- Common investigations
                                         INDEX idx_auth_audit_log_actor_time (actor_type, actor_id, occurred_at),
                                         INDEX idx_auth_audit_log_target_time (target_type, target_id, occurred_at),
                                         INDEX idx_auth_audit_log_correlation_time (correlation_id, occurred_at),
                                         INDEX idx_auth_audit_log_action_time (action, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Materialized authoritative audit log. MUST be written ONLY by outbox consumer. Not authoritative. Changes must be structured & minimal; no secrets.';


-- ==========================================================
-- 2) Audit Trail (DATA ACCESS / VIEWS / NAVIGATION / EXPORTS)
-- ----------------------------------------------------------
-- Answers: "Who accessed what sensitive thing, when?"
-- This is NOT authoritative state change.
-- ==========================================================

CREATE TABLE audit_trail (
                             id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID per row/event (portable)
                             event_id CHAR(36) NOT NULL,

    -- Who performed the access
                             actor_type VARCHAR(32) NOT NULL,
                             actor_id BIGINT NULL,

    -- Examples: customer.view, customer.export, page.visit, invoice.download
                             event_key VARCHAR(255) NOT NULL,

    -- Accessed resource (what was touched)
                             entity_type VARCHAR(64) NOT NULL,
                             entity_id BIGINT NULL,

    -- "On behalf of" / data subject (e.g., the customer whose data was viewed)
                             subject_type VARCHAR(64) NULL,
                             subject_id BIGINT NULL,

    -- Navigation context (store sanitized values; DO NOT store sensitive query strings)
                             referrer_route_name VARCHAR(255) NULL,
                             referrer_path VARCHAR(1024) NULL,  -- recommended: path only, no query
                             referrer_host VARCHAR(255) NULL,   -- optional

    -- Correlation to request pipeline
                             correlation_id CHAR(36) NULL,
                             request_id VARCHAR(64) NULL,
                             route_name VARCHAR(255) NULL,

    -- Request context
                             ip_address VARCHAR(45) NULL,
                             user_agent VARCHAR(512) NULL,

    -- Extra metadata (MUST NOT contain secrets)
                             metadata JSON NOT NULL,

                             occurred_at DATETIME(6) NOT NULL,

                             UNIQUE KEY uq_audit_trail_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                             INDEX idx_audit_trail_time (occurred_at, id),

    -- Common investigations (two required search dimensions: actor+time and key+time)
                             INDEX idx_audit_trail_actor_time (actor_type, actor_id, occurred_at),
                             INDEX idx_audit_trail_event_time (event_key, occurred_at),

    -- Deep investigations
                             INDEX idx_audit_trail_entity_time (entity_type, entity_id, occurred_at),
                             INDEX idx_audit_trail_subject_time (subject_type, subject_id, occurred_at),

    -- Correlation helpers
                             INDEX idx_audit_trail_correlation_time (correlation_id, occurred_at),
                             INDEX idx_audit_trail_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Audit Trail: records data exposure/navigation/views/exports. Searchable by actor+time and event_key+time. Store sanitized referrer_path (no tokens/OTP/query secrets).';


-- ==========================================================
-- 3) Security Signals (NON-AUTH / BEST-EFFORT)
-- ----------------------------------------------------------
-- Answers: "What security-relevant signals happened?"
-- MUST NOT affect control-flow. MUST tolerate failure.
-- ==========================================================

CREATE TABLE security_signals (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID per signal/event (portable)
                                  event_id CHAR(36) NOT NULL,

    -- Actor involved (can be anonymous for pre-auth signals)
                                  actor_type VARCHAR(32) NOT NULL,
                                  actor_id BIGINT NULL,

    -- Examples: login_failed, permission_denied, session_invalid...
                                  signal_type VARCHAR(100) NOT NULL,

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                  severity VARCHAR(16) NOT NULL,

    -- Correlation to request pipeline
                                  correlation_id CHAR(36) NULL,
                                  request_id VARCHAR(64) NULL,
                                  route_name VARCHAR(255) NULL,

    -- Request context
                                  ip_address VARCHAR(45) NULL,
                                  user_agent VARCHAR(512) NULL,

    -- Additional info (MUST NOT contain secrets)
                                  metadata JSON NOT NULL,

                                  occurred_at DATETIME(6) NOT NULL,

                                  UNIQUE KEY uq_security_signals_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                  INDEX idx_security_signals_time (occurred_at, id),

    -- Required search dimensions
                                  INDEX idx_security_signals_actor_time (actor_type, actor_id, occurred_at),
                                  INDEX idx_security_signals_type_time (signal_type, occurred_at),

    -- Dashboards / alerting
                                  INDEX idx_security_signals_severity_time (severity, occurred_at),

    -- Correlation helpers
                                  INDEX idx_security_signals_correlation_time (correlation_id, occurred_at),
                                  INDEX idx_security_signals_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Security signals for detection/alerting (non-authoritative). Best-effort; MUST NOT block user actions. Metadata MUST NOT contain secrets (passwords, OTP codes, tokens).';


-- ==========================================================
-- 4) Operational Activity (MUTATIONS ONLY)
-- ----------------------------------------------------------
-- Answers: "What operational changes did the actor do?"
-- Strictly mutations (create/update/delete). No reads/views here.
-- ==========================================================

CREATE TABLE operational_activity (
                                      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                      event_id CHAR(36) NOT NULL,

                                      actor_type VARCHAR(32) NOT NULL,
                                      actor_id BIGINT NULL,

    -- Examples: create/update/delete/bulk_action/report_run...
                                      action VARCHAR(128) NOT NULL,

    -- Affected entity (optional)
                                      entity_type VARCHAR(64) NULL,
                                      entity_id BIGINT NULL,

    -- Metadata about the operation (MUST NOT contain secrets)
                                      metadata JSON NOT NULL,

                                      correlation_id CHAR(36) NULL,
                                      request_id VARCHAR(64) NULL,
                                      route_name VARCHAR(255) NULL,

                                      ip_address VARCHAR(45) NULL,
                                      user_agent VARCHAR(512) NULL,

                                      occurred_at DATETIME(6) NOT NULL,

                                      UNIQUE KEY uq_operational_activity_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                      INDEX idx_operational_activity_time (occurred_at, id),

    -- Required search dimensions
                                      INDEX idx_operational_activity_actor_time (actor_type, actor_id, occurred_at),
                                      INDEX idx_operational_activity_action_time (action, occurred_at),

    -- Deep investigations
                                      INDEX idx_operational_activity_entity_time (entity_type, entity_id, occurred_at),

    -- Correlation helpers
                                      INDEX idx_operational_activity_correlation_time (correlation_id, occurred_at),
                                      INDEX idx_operational_activity_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Operational activity for mutations only. Searchable by actor+time and action+time. Views/reads are NOT allowed (use audit_trail).';


-- ==========================================================
-- 5) Diagnostics Telemetry (TECHNICAL / NON-BUSINESS)
-- ----------------------------------------------------------
-- Answers: "How did the system behave?" (performance/diagnostics).
-- Non-business, best-effort, no compliance meaning.
-- ==========================================================

CREATE TABLE diagnostics_telemetry (
                                       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                       event_id CHAR(36) NOT NULL,

    -- Examples: http.request, db.query, exception, cache.miss...
                                       event_key VARCHAR(255) NOT NULL,

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                       severity VARCHAR(16) NOT NULL DEFAULT 'INFO',

                                       actor_type VARCHAR(32) NOT NULL,
                                       actor_id BIGINT NULL,

                                       correlation_id CHAR(36) NULL,
                                       request_id VARCHAR(64) NULL,
                                       route_name VARCHAR(255) NULL,

                                       ip_address VARCHAR(45) NULL,
                                       user_agent VARCHAR(512) NULL,

    -- Duration for timing metrics (optional)
                                       duration_ms INT UNSIGNED NULL,

    -- Additional diagnostics metadata (avoid PII; never store secrets)
                                       metadata JSON NULL,

                                       occurred_at DATETIME(6) NOT NULL,

                                       UNIQUE KEY uq_diagnostics_telemetry_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                       INDEX idx_diag_telemetry_time (occurred_at, id),

    -- Required search dimensions
                                       INDEX idx_diag_telemetry_actor_time (actor_type, actor_id, occurred_at),
                                       INDEX idx_diag_telemetry_event_time (event_key, occurred_at),

    -- Dashboards
                                       INDEX idx_diag_telemetry_severity_time (severity, occurred_at),

    -- Correlation helpers
                                       INDEX idx_diag_telemetry_correlation_time (correlation_id, occurred_at),
                                       INDEX idx_diag_telemetry_request_time (request_id, occurred_at),
                                       INDEX idx_diag_telemetry_route_time (route_name, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Diagnostics telemetry for tracing/performance/technical errors (non-business). Searchable by event_key+time and actor+time. Avoid PII; prefer hashed identifiers if needed.';


-- ==========================================================
-- 6) Delivery Operations (NOTIFICATIONS / JOBS / WEBHOOKS)
-- ----------------------------------------------------------
-- Answers: "What delivery/queue/job operations happened and their status?"
-- Best-effort domain for operational observability & retries.
-- ==========================================================

CREATE TABLE delivery_operations (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                     event_id CHAR(36) NOT NULL,

    -- Delivery channel (email/telegram/sms/webhook/push/job)
                                     channel VARCHAR(32) NOT NULL,

    -- Operation type (notification_send/webhook_deliver/job_run/...)
                                     operation_type VARCHAR(64) NOT NULL,

    -- Who initiated the operation (can be NULL for pure system jobs)
                                     actor_type VARCHAR(32) NULL,
                                     actor_id BIGINT NULL,

    -- Target of the delivery (optional)
                                     target_type VARCHAR(64) NULL,
                                     target_id BIGINT NULL,

    -- Status (queued/sent/delivered/failed/retrying/cancelled...)
                                     status VARCHAR(32) NOT NULL,

    -- Retry counter
                                     attempt_no INT UNSIGNED NOT NULL DEFAULT 0,

    -- Lifecycle timestamps (optional)
                                     scheduled_at DATETIME(6) NULL,
                                     completed_at DATETIME(6) NULL,

                                     correlation_id CHAR(36) NULL,
                                     request_id VARCHAR(64) NULL,

    -- Provider metadata (optional)
                                     provider VARCHAR(64) NULL,
                                     provider_message_id VARCHAR(128) NULL,

    -- Failure details (best-effort; no secrets)
                                     error_code VARCHAR(64) NULL,
                                     error_message TEXT NULL,

    -- Additional metadata (MUST NOT contain secrets)
                                     metadata JSON NOT NULL,

                                     occurred_at DATETIME(6) NOT NULL,

                                     UNIQUE KEY uq_delivery_operations_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                     INDEX idx_delivery_ops_time (occurred_at, id),

    -- Required search dimensions
                                     INDEX idx_delivery_ops_actor_time (actor_type, actor_id, occurred_at),
                                     INDEX idx_delivery_ops_channel_time (channel, occurred_at),
                                     INDEX idx_delivery_ops_type_time (operation_type, occurred_at),
                                     INDEX idx_delivery_ops_status_time (status, occurred_at),

    -- Deep investigations
                                     INDEX idx_delivery_ops_target_time (target_type, target_id, occurred_at),

    -- Correlation helpers
                                     INDEX idx_delivery_ops_correlation_time (correlation_id, occurred_at),
                                     INDEX idx_delivery_ops_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Delivery/system operations for notifications, queues, jobs, webhooks. Searchable by channel/type/status+time and actor+time. Track retries & providers. No secrets in metadata.';


-- ==========================================================
-- 7) Generic Checkpoints (RESERVED)
-- ----------------------------------------------------------
-- Generic cursor checkpoints for future processors:
-- - archivers (hot -> archive)
-- - materializers (outbox -> log)
-- - exporters (MySQL -> external)
-- Not used by baseline itself.
-- ==========================================================

CREATE TABLE log_processing_checkpoints (
                                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Logical stream identifier (e.g. audit_trail, security_signals, diagnostics_telemetry)
                                            log_stream VARCHAR(64) NOT NULL,

    -- Processor name for uniqueness if multiple processors exist in future
                                            processor VARCHAR(64) NOT NULL,

    -- Cursor: last successfully processed row (ordered by occurred_at then id)
                                            last_processed_occurred_at DATETIME(6) NULL,
                                            last_processed_mysql_id BIGINT UNSIGNED NULL,

    -- Operational metadata
                                            last_run_started_at DATETIME(6) NULL,
                                            last_run_finished_at DATETIME(6) NULL,
                                            last_batch_count INT UNSIGNED NOT NULL DEFAULT 0,

    -- Failure tracking (best-effort; do not store secrets)
                                            last_error_code VARCHAR(64) NULL,
                                            last_error_message VARCHAR(512) NULL,

                                            updated_at DATETIME(6) NOT NULL,

                                            UNIQUE KEY uq_log_processing_checkpoint (log_stream, processor),
                                            INDEX idx_log_processing_checkpoints_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='RESERVED: Generic checkpoints for future log processors (materializers/archivers/exporters). Cursor order: occurred_at ASC, id ASC. NOT USED in current baseline.';
