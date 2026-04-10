
-- ==========================================================
-- Audit Trail (DATA ACCESS / VIEWS / NAVIGATION / EXPORTS)
-- ----------------------------------------------------------
-- Answers: "Who accessed what sensitive thing, when?"
-- This is NOT authoritative state change.
-- ==========================================================

CREATE TABLE audit_trail (
                             id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID per row/event (portable)
                             event_id CHAR(36) NOT NULL,

    -- Who performed the access
                             actor_type VARCHAR(32) NOT NULL,
                             actor_id BIGINT NULL,

    -- Examples: customer.view, customer.export, page.visit, invoice.download
                             event_key VARCHAR(255) NOT NULL,

    -- Accessed resource (what was touched)
                             entity_type VARCHAR(64) NOT NULL,
                             entity_id BIGINT NULL,

    -- "On behalf of" / data subject (e.g., the customer whose data was viewed)
                             subject_type VARCHAR(64) NULL,
                             subject_id BIGINT NULL,

    -- Navigation context (store sanitized values; DO NOT store sensitive query strings)
                             referrer_route_name VARCHAR(255) NULL,
                             referrer_path VARCHAR(1024) NULL,  -- recommended: path only, no query
                             referrer_host VARCHAR(255) NULL,   -- optional

    -- Correlation to request pipeline
                             correlation_id CHAR(36) NULL,
                             request_id VARCHAR(64) NULL,
                             route_name VARCHAR(255) NULL,

    -- Request context
                             ip_address VARCHAR(45) NULL,
                             user_agent VARCHAR(512) NULL,

    -- Extra metadata (MUST NOT contain secrets)
                             metadata JSON NOT NULL,

                             occurred_at DATETIME(6) NOT NULL,

                             UNIQUE KEY uq_audit_trail_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                             INDEX idx_audit_trail_time (occurred_at, id),

    -- Common investigations (two required search dimensions: actor+time and key+time)
                             INDEX idx_audit_trail_actor_time (actor_type, actor_id, occurred_at),
                             INDEX idx_audit_trail_event_time (event_key, occurred_at),

    -- Deep investigations
                             INDEX idx_audit_trail_entity_time (entity_type, entity_id, occurred_at),
                             INDEX idx_audit_trail_subject_time (subject_type, subject_id, occurred_at),

    -- Correlation helpers
                             INDEX idx_audit_trail_correlation_time (correlation_id, occurred_at),
                             INDEX idx_audit_trail_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Audit Trail: records data exposure/navigation/views/exports. Searchable by actor+time and event_key+time. Store sanitized referrer_path (no tokens/OTP/query secrets).';
