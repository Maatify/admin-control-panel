# Patch Plan: Legacy Security Logger Adapter + DI Rebind

## 1. File List

| Action | File Path | Description |
| :--- | :--- | :--- |
| **CREATE** | `app/Infrastructure/Adapter/LegacySecurityEventLoggerAdapter.php` | The adapter class implementing `App\Domain\Contracts\SecurityEventLoggerInterface`. |
| **MODIFY** | `app/Bootstrap/Container.php` | Rebind `SecurityEventLoggerInterface` to use the new adapter instead of the broken repository. |
| **MODIFY** | `app/Modules/SecurityEvents/Enum/SecurityEventTypeEnum.php` | Add `LEGACY_UNMAPPED` case to support unknown legacy event strings without misleading defaults. |

---

## 2. Adapter Specification

**Class:** `App\Infrastructure\Adapter\LegacySecurityEventLoggerAdapter`
**Implements:** `App\Domain\Contracts\SecurityEventLoggerInterface`
**Dependencies:** `App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface`

### Method Logic: `log(SecurityEventDTO $legacy)`

1.  **Actor Resolution:**
    - If `$legacy->adminId` is present: `ActorType::ADMIN`, `ActorId::(int)$legacy->adminId`.
    - Else: `ActorType::ANONYMOUS`, `ActorId::null`.

2.  **Mapping Rules (Strict):**

| Legacy Event String | Target Enum Case (`SecurityEventTypeEnum`) | Notes |
| :--- | :--- | :--- |
| `admin_logout` | `LOGOUT` | Direct semantic match. |
| `login_failed` | `LOGIN_FAILED` | Direct semantic match. |
| `login_blocked` | `LOGIN_FAILED` | Semantically a failure. Metadata preserves "blocked". |
| `permission_denied` | `PERMISSION_DENIED` | Direct semantic match. |
| `session_validation_failed` | `SESSION_INVALID` | Direct semantic match. |
| `recovery_action_blocked` | `PERMISSION_DENIED` | Closest match (denial). Metadata preserves "recovery". |
| `password_changed` | `LEGACY_UNMAPPED` | No `PASSWORD_CHANGED` or `PROFILE_UPDATE` case exists. |
| `remember_me_issued` | `LEGACY_UNMAPPED` | No "TOKEN_ISSUED" case exists. |
| `remember_me_rotated` | `LEGACY_UNMAPPED` | No "TOKEN_ROTATED" case exists. |
| `remember_me_revoked` | `LEGACY_UNMAPPED` | No "TOKEN_REVOKED" case exists. |
| `remember_me_theft_suspected` | `LEGACY_UNMAPPED` | CRITICAL event. Must map to `LEGACY_UNMAPPED` to preserve exactness. |
| *(Any Other)* | `LEGACY_UNMAPPED` | **Fail-Safe:** Never guess. |

3.  **Metadata Population:**
    - Initialize with `$legacy->context`.
    - Add `legacy_event_name` => `$legacy->eventName`.
    - Add `legacy_severity` => `$legacy->severity`.
    - Preserve `reason` or other context fields.

4.  **Severity Mapping:**
    - `critical` -> `CRITICAL`
    - `warning` -> `WARNING`
    - `error` -> `ERROR`
    - `info` -> `INFO`
    - Default/Unknown -> `INFO`

---

## 3. Container Binding Change

**File:** `app/Bootstrap/Container.php`

**Search:**
```php
            SecurityEventLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new SecurityEventRepository($pdo);
            },
```

**Replace With:**
```php
            SecurityEventLoggerInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(SecurityEventRecorderInterface::class);
                assert($recorder instanceof SecurityEventRecorderInterface);

                return new \App\Infrastructure\Adapter\LegacySecurityEventLoggerAdapter($recorder);
            },
```

---

## 4. Test Impact Notes

- **Unit Tests:** Existing unit tests that mock `SecurityEventLoggerInterface` (e.g., `AdminAuthenticationServiceTest`) will pass without modification because the interface contract is unchanged.
- **Integration Tests:** Any flow relying on `SecurityEventRepository` implicitly (by checking the DB) might have been failing silently or checking the wrong table. Since the repository was broken (wrong columns), tests likely mocked it or asserted nothing.
- **New Coverage:**
    - Verify that `LEGACY_UNMAPPED` events land in `security_events` table with `event_type = 'legacy_unmapped'` and full metadata.
    - Verify that `admin_logout` lands as `logout`.
