# UI Execution Rules (Canonical v2)

## 1. Canonical Frontend Architecture

1. `AdminPageBridge` is the primary frontend orchestration facade.
2. Family-local helper layer is required orchestration seam (e.g., `I18nHelpersV2`).
3. `Bridge.Table` + helper wrappers define page-level table orchestration.
4. New/edited v2 work must follow bridge-first conventions.

---

## 2. Twig ↔ JS Runtime Contract (Two-Tier)

### Tier A — Default/simple table pages
- Use `<div id="table-container" class="w-full"></div>`.
- No custom table container runtime global required.

### Tier B — Non-default/complex pages
- Twig must inject explicit globals, at minimum:
  - page context object (if route context is needed), and/or
  - `window.{feature}TableContainerId` for non-default table target.
- JS must consume these globals; do not infer context from brittle URL parsing when context is provided.

---

## 3. Script Mount Order (v2 pages)

In `{% block scripts %}`:
1. required infra scripts (`api_handler.js`, `data_table.js`, etc. as needed)
2. `admin-page-bridge.js`
3. family helper v2 file
4. page v2 module(s)

---

## 4. API Rules (v2)

- Preferred path: `AdminPageBridge.API.execute(...)` and `AdminPageBridge.API.runMutation(...)`.
- Direct `ApiHandler.call(...)` is legacy/hybrid fallback only.
- Endpoint strings must not include `/api/` prefix.
- Direct `fetch()/axios` for JSON APIs is forbidden (file-upload exception remains).

---

## 5. Table Rules (v2)

- Use helper/bridge table targeting and scoped tableAction handling.
- Prefer:
  - `I18nHelpersV2.withTableContainerTarget(...)`
  - `I18nHelpersV2.bindTableActionState(...)`
  - `I18nHelpersV2.createResetPageReload(...)`
- Note: helper names above are illustrative examples of the required family-local helper pattern; use the equivalent helper names for the active feature family.
- Avoid broad unscoped `document.addEventListener('tableAction', ...)` in new/edited v2 pages.

---

## 6. Modal Rules (v2)

- Modal strategy is parity-driven: static Twig modal or dynamic injected modal are both valid.
- For new/edited v2 modal flows, use helper-level dismiss wiring (e.g., `wireModalDismiss`) where applicable.
- Do not enforce rigid modal bans that conflict with page parity requirements.

---

## 7. Escaping/XSS Rule (v2)

- Use `AdminPageBridge.Text.escapeHtml(...)` for renderer/template escaping in v2 modules.
- Local `escapeHtml` function is fallback only for legacy/hybrid files where Bridge is unavailable.

---

## 8. File Shape Rule (v2)

- Use the smallest safe v2 shape:
  - single page module when bounded and parity-safe,
  - split family modules only when interaction complexity clearly requires it.
- Fixed split is not mandatory by default.

---

## 9. Controlled Globals & Reload Naming

- Allow only controlled page globals:
  - capabilities contract,
  - explicit context/container contracts,
  - reload hooks.
- New reload hook naming should follow `...V2` direction.
- Keep non-v2 aliases only when compatibility requires them.

---

## 10. Superseded Legacy Files

- If active runtime is already owned by a v2 family, do not auto-migrate redundant legacy files.
- Treat such files as superseded unless unique behavior is proven.
