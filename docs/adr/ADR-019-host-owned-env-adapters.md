# ADR-019: Host-Owned Environment Adapters Live Outside AdminKernel

**Status:** ACCEPTED
**Date:** 2026-09-05
**Decision ID:** ADR-019

---

## 1. Context

[ADR (external plan)](../architecture/ADMIN_ENV_NAMESPACING_REMEDIATION_PLAN.md) namespaced every
Admin-owned environment key under `ADMIN_*`, so this host can run alongside another application
without colliding on generic names like `APP_ENV` or `DB_HOST`.

`AdminKernel`'s own internal contracts (`AdminRuntimeConfigDTO`, `MediaUrlConfigDTO`, and the
`Storage` module's `StorageConfig`) intentionally keep reading the **generic**, non-namespaced
names (`APP_ENV`, `DB_HOST`, `STORAGE_DRIVER`, ...). This is required for `AdminKernel` to stay
usable by a different host (it is synced from `Athar`; see `697486ed Sync AdminKernel contracts
with Athar`) without that host being forced into an `ADMIN_*` naming scheme it never chose.

Translating `ADMIN_*` into the generic contract therefore has to happen somewhere. The two adapter
classes that do this translation — `AdminEnvironmentAdapter` and `AdminStorageEnvAdapter` — were
initially added under `Modules/AdminKernel/Bootstrap/`, i.e. physically inside the reusable Kernel
module's own namespace (`Maatify\AdminKernel\Bootstrap\...`).

That placement was wrong: the `ADMIN_` prefix is this host's own naming decision, not part of
`AdminKernel`'s reusable contract. Any future sync of `Modules/AdminKernel` with `Athar` (or reuse
by another host) would have carried this project's `ADMIN_*` assumption along with it.

## 2. Decision

Host-boundary environment adapters that translate a host's own namespaced environment variables
into a reusable module's generic contract **must live under `app/`** (`Maatify\AdminControlPanel\`),
never under `Modules/AdminKernel/` or any other reusable module folder.

Concretely:

* `AdminEnvironmentAdapter` and `AdminStorageEnvAdapter` live in `app/Bootstrap/`
  (`Maatify\AdminControlPanel\Bootstrap\...`).
* `public/admin/index.php` (the composition root) is the only caller that wires these adapters into
  `AdminRuntimeConfigDTO::fromArray()`, `MediaUrlConfigDTO::fromArray()`, and `StorageConfig`.
* `Modules/AdminKernel` and `Modules/Storage` never read `ADMIN_*` directly — they only ever see the
  generic contract, matching acceptance criterion 11 of the env-namespacing plan.

This is the standard Anti-Corruption Layer / Boundary Adapter pattern: a reusable module speaks its
own neutral language, and translation from any specific host's vocabulary happens once, at that
host's own composition boundary — never inside the module being protected.

## 3. Rationale

### 3.1 `AdminKernel` extraction/reuse readiness

`AdminKernel` is reused/synced with `Athar`. If it shipped its own `ADMIN_*`-aware adapter, every
host reusing it would inherit an opinion about naming that isn't theirs to inherit. Keeping the
adapter in `app/` means cloning or extracting `Modules/AdminKernel` alone carries zero assumptions
about any specific host's env-variable names.

### 3.2 Precedent for future host-owned modules

The same reasoning was applied when reserving [Modules/Telegram](../../Modules/Telegram/docs/ADR-009-Telegram-Delivery-Independent-Queue.md)
as the future home for Telegram delivery: code and contracts that are genuinely host-specific (or
not yet fully generalized) do not get placed inside `Modules/AdminKernel`, even temporarily.

## 4. Consequences

* **Positive:** `Modules/AdminKernel` can be synced with `Athar` or reused by another host without
  carrying this project's `ADMIN_*` naming choice.
* **Positive:** Any other host reusing `AdminKernel` writes its own equivalent adapter in its own
  `app/`-equivalent space, without touching `Modules/AdminKernel`.
* **Guardrail:** No new class under `Modules/AdminKernel` may read an `ADMIN_*` key directly. New
  host-boundary translation code is added under `app/Bootstrap/`, not inside the Kernel module.
