CREATE TABLE delivery_operations (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                     event_id CHAR(36) NOT NULL,

    -- Delivery channel (email/telegram/sms/webhook/push/job)
                                     channel VARCHAR(32) NOT NULL,

    -- Operation type (notification_send/webhook_deliver/job_run/...)
                                     operation_type VARCHAR(64) NOT NULL,

    -- Who initiated the operation (can be NULL for pure system jobs)
                                     actor_type VARCHAR(32) NULL,
                                     actor_id BIGINT NULL,

    -- Target of the delivery (optional)
                                     target_type VARCHAR(64) NULL,
                                     target_id BIGINT NULL,

    -- Status (queued/sent/delivered/failed/retrying/cancelled...)
                                     status VARCHAR(32) NOT NULL,

    -- Retry counter
                                     attempt_no INT UNSIGNED NOT NULL DEFAULT 0,

    -- Lifecycle timestamps (optional)
                                     scheduled_at DATETIME(6) NULL,
                                     completed_at DATETIME(6) NULL,

                                     correlation_id CHAR(36) NULL,
                                     request_id VARCHAR(64) NULL,

    -- Provider metadata (optional)
                                     provider VARCHAR(64) NULL,
                                     provider_message_id VARCHAR(128) NULL,

    -- Failure details (best-effort; no secrets)
                                     error_code VARCHAR(64) NULL,
                                     error_message TEXT NULL,

    -- Additional metadata (MUST NOT contain secrets)
                                     metadata JSON NOT NULL,

                                     occurred_at DATETIME(6) NOT NULL,

                                     UNIQUE KEY uq_delivery_operations_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                     INDEX idx_delivery_ops_time (occurred_at, id),

    -- Required search dimensions
                                     INDEX idx_delivery_ops_actor_time (actor_type, actor_id, occurred_at),
                                     INDEX idx_delivery_ops_channel_time (channel, occurred_at),
                                     INDEX idx_delivery_ops_type_time (operation_type, occurred_at),
                                     INDEX idx_delivery_ops_status_time (status, occurred_at),

    -- Deep investigations
                                     INDEX idx_delivery_ops_target_time (target_type, target_id, occurred_at),

    -- Correlation helpers
                                     INDEX idx_delivery_ops_correlation_time (correlation_id, occurred_at),
                                     INDEX idx_delivery_ops_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Delivery/system operations for notifications, queues, jobs, webhooks. Searchable by channel/type/status+time and actor+time. Track retries & providers. No secrets in metadata.';
