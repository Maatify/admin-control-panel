CREATE TABLE IF NOT EXISTS audit_logs (
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

CREATE TABLE IF NOT EXISTS security_events (
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
