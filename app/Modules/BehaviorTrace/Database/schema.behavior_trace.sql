CREATE TABLE operational_activity (
                                       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                       event_id CHAR(36) NOT NULL,

    -- Examples: customer.update, settings.change
                                       action VARCHAR(255) NOT NULL,

                                       actor_type VARCHAR(32) NOT NULL,
                                       actor_id BIGINT NULL,

                                       correlation_id CHAR(36) NULL,
                                       request_id VARCHAR(64) NULL,
                                       route_name VARCHAR(255) NULL,

                                       ip_address VARCHAR(45) NULL,
                                       user_agent VARCHAR(512) NULL,

    -- Additional operational metadata (avoid PII; never store secrets)
                                       metadata JSON NULL,

                                       occurred_at DATETIME(6) NOT NULL,

                                       UNIQUE KEY uq_operational_activity_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                       INDEX idx_ops_activity_time (occurred_at, id),

    -- Required search dimensions
                                       INDEX idx_ops_activity_actor_time (actor_type, actor_id, occurred_at),
                                       INDEX idx_ops_activity_action_time (action, occurred_at),

    -- Correlation helpers
                                       INDEX idx_ops_activity_correlation_time (correlation_id, occurred_at),
                                       INDEX idx_ops_activity_request_time (request_id, occurred_at),
                                       INDEX idx_ops_activity_route_time (route_name, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Operational activity for tracking mutations and state changes (non-read). Searchable by action+time and actor+time.';
