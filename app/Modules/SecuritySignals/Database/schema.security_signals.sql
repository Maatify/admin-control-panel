-- ==========================================================
-- 3) Security Signals (NON-AUTH / BEST-EFFORT)
-- ----------------------------------------------------------
-- Answers: "What security-relevant signals happened?"
-- MUST NOT affect control-flow. MUST tolerate failure.
-- ==========================================================

CREATE TABLE security_signals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id CHAR(36) NOT NULL,
  actor_type VARCHAR(32) NOT NULL,
  actor_id BIGINT NULL,
  signal_type VARCHAR(100) NOT NULL,
  severity VARCHAR(16) NOT NULL,
  correlation_id CHAR(36) NULL,
  request_id VARCHAR(64) NULL,
  route_name VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  metadata JSON NOT NULL,
  occurred_at DATETIME(6) NOT NULL,
  UNIQUE KEY uq_security_signals_event_id (event_id),
  INDEX idx_security_signals_time (occurred_at, id),
  INDEX idx_security_signals_actor_time (actor_type, actor_id, occurred_at),
  INDEX idx_security_signals_type_time (signal_type, occurred_at),
  INDEX idx_security_signals_severity_time (severity, occurred_at),
  INDEX idx_security_signals_correlation_time (correlation_id, occurred_at),
  INDEX idx_security_signals_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Security signals for detection/alerting (non-authoritative). Best-effort; MUST NOT block user actions. Metadata MUST NOT contain secrets (passwords, OTP codes, tokens).';
