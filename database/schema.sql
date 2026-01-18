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
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
                              UNIQUE KEY uq_admin_email_blind_index (email_blind_index),
                              CONSTRAINT fk_ae_admin_id
                                  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_passwords (
                                 admin_id INT PRIMARY KEY,
                                 password_hash VARCHAR(255) NOT NULL,
                                 pepper_id VARCHAR(16) NOT NULL,
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
                                CONSTRAINT fk_as_admin_id
                                    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
 * AUDIT / LOGGING
 * =========================== */

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

CREATE TABLE audit_outbox (
                              id BIGINT AUTO_INCREMENT PRIMARY KEY,
                              actor_type varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
                              actor_id BIGINT NULL,
                              action VARCHAR(128) NOT NULL,
                              target_type VARCHAR(64) NOT NULL,
                              target_id BIGINT NULL,
                              risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
                              payload JSON NOT NULL,
                              correlation_id CHAR(36) NOT NULL,
                              created_at DATETIME NOT NULL,
                              INDEX idx_audit_actor (actor_type, actor_id),
                              INDEX idx_audit_target (target_type, target_id),
                              INDEX idx_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE security_events (
                                 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                 actor_type VARCHAR(32) NOT NULL,
                                 actor_id INT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
                               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                               actor_type VARCHAR(32) NOT NULL,
                               actor_id INT NULL,
                               action VARCHAR(128) NOT NULL,
                               entity_type VARCHAR(64) NULL,
                               entity_id INT NULL,
                               metadata JSON NULL,
                               ip_address VARCHAR(45) NULL,
                               user_agent VARCHAR(255) NULL,
                               request_id VARCHAR(64) NULL,
                               occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                               INDEX idx_actor (actor_type, actor_id),
                               INDEX idx_action (action),
                               INDEX idx_entity (entity_type, entity_id),
                               INDEX idx_occurred_at (occurred_at),
                               INDEX idx_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE telemetry_traces (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                  event_key VARCHAR(255) NOT NULL,
                                  severity VARCHAR(16) NOT NULL DEFAULT 'info',
                                  route_name VARCHAR(255) NULL,
                                  request_id VARCHAR(64) NULL,
                                  actor_type VARCHAR(32) NOT NULL,
                                  actor_id INT NULL,
                                  ip_address VARCHAR(45) NULL,
                                  user_agent TEXT NULL,
                                  metadata JSON NULL,
                                  occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                  INDEX idx_telemetry_actor (actor_type, actor_id),
                                  INDEX idx_telemetry_event_key (event_key),
                                  INDEX idx_telemetry_severity (severity),
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
