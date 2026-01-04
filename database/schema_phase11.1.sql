CREATE TABLE IF NOT EXISTS admin_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    notification_type VARCHAR(64) NOT NULL,
    channel_type VARCHAR(32) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_type_channel (admin_id, notification_type, channel_type),
    CONSTRAINT fk_anp_admin_id FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
);
