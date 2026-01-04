CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    notification_type VARCHAR(64) NOT NULL,
    channel_type VARCHAR(32) NOT NULL,
    intent_id VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    CONSTRAINT fk_an_admin_id FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
);
