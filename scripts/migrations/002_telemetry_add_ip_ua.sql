ALTER TABLE telemetry_traces ADD COLUMN ip_address VARCHAR(45) NULL AFTER actor_admin_id;
ALTER TABLE telemetry_traces ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address;
