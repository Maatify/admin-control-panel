CREATE TABLE IF NOT EXISTS verification_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    subject_type VARCHAR(32) NOT NULL,
    subject_identifier VARCHAR(255) NOT NULL,

    purpose VARCHAR(64) NOT NULL,

    code_hash CHAR(64) NOT NULL,

    status ENUM('active','used','expired') NOT NULL DEFAULT 'active',

    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts INT UNSIGNED NOT NULL,

    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_subject_purpose (subject_type, subject_identifier, purpose),
    INDEX idx_status_expiry (status, expires_at)
) ENGINE=InnoDB;
