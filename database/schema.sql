SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS system_ownership;
DROP TABLE IF EXISTS admin_direct_permissions;
DROP TABLE IF EXISTS step_up_grants;
DROP TABLE IF EXISTS audit_outbox;
DROP TABLE IF EXISTS verification_codes;
DROP TABLE IF EXISTS admin_notification_channels;
DROP TABLE IF EXISTS admin_notification_preferences;
DROP TABLE IF EXISTS admin_notifications;
DROP TABLE IF EXISTS failed_notifications;
DROP TABLE IF EXISTS security_events;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS admin_roles;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_passwords;
DROP TABLE IF EXISTS admin_emails;
DROP TABLE IF EXISTS admins;

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
    email_encrypted TEXT NOT NULL,
    verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_email_blind_index (email_blind_index),
    CONSTRAINT fk_ae_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_passwords (
    admin_id INT PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    event_name VARCHAR(255) NOT NULL,
    context JSON NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    occurred_at DATETIME NOT NULL,
    INDEX idx_security_admin_id (admin_id),
    INDEX idx_security_event_name (event_name),
    INDEX idx_security_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
