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
