# Compliance Checklist

- [x] **Domain Authority:** Classified as `Operational Activity`.
- [x] **One-Domain Rule:** Does not accept Telemetry or Audit events.
- [x] **Fail-Open:** Recorder swallows storage exceptions.
- [x] **Structure:** Follows `LOGGING_LIBRARY_STRUCTURE_CANONICAL.md`.
- [x] **Database:** Uses `operational_activity` table.
- [x] **Schema:** Columns match canonical standard (no invention).
- [x] **DTOs:** Strict usage of DTOs for internal passing.
- [x] **Safety:** No secrets, 64KB metadata limit.
- [x] **Timezone:** `occurred_at` is UTC.
- [x] **Context:** Includes standard normalized context fields.
