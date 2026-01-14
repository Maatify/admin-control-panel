CREATE TABLE telemetry_traces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(255) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    actor_admin_id INT NULL,
    route_name VARCHAR(255) NULL,
    request_id VARCHAR(64) NULL,
    metadata JSON NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_created_at (created_at),
    INDEX idx_event_key (event_key),
    INDEX idx_actor (actor_admin_id),
    INDEX idx_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
