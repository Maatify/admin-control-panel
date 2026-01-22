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
