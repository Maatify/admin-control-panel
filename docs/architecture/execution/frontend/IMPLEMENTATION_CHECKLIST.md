# Implementation Checklist (Canonical v2)

Use this checklist for new/edited frontend work in v2-active areas.

## 1. Architecture Selection

- [ ] Confirm feature/page is v2-active (or explicitly legacy/hybrid).
- [ ] Choose smallest safe v2 shape:
  - [ ] single v2 page module
  - [ ] split modules only when complexity clearly requires
- [ ] Do not auto-migrate redundant legacy files already superseded by active v2 family.

---

## 2. Twig Contract Checks

- [ ] `window.{feature}Capabilities` injected in `{% block content %}`.
- [ ] If route/entity context is needed: `window.{feature}Context` injected explicitly.
- [ ] Table contract follows two-tier policy:
  - [ ] Tier A default: `#table-container`
  - [ ] Tier B non-default: explicit `window.{feature}TableContainerId`
- [ ] `{% block scripts %}` uses canonical v2 mount order:
  - [ ] infra scripts
  - [ ] `admin-page-bridge.js`
  - [ ] family helper v2
  - [ ] page v2 module(s)

---

## 3. v2 JS Orchestration Checks

- [ ] Module uses `AdminPageBridge` as primary facade.
- [ ] Module uses family helper seam for table/reset/modal orchestration where available.
- [ ] Table orchestration uses scoped bridge/helper path (`withTableContainerTarget`, `bindTableActionState`).
- [ ] API path uses `Bridge.API.execute/runMutation` (not ApiHandler-first in new v2 code).
- [ ] Escape path uses `Bridge.Text.escapeHtml(...)` in v2 renderers/templates.
- [ ] Modal dismiss lifecycle uses helper-level dismiss wiring for new/edited v2 modal flows.

---

## 4. State/Reload/Global Checks

- [ ] Filter/search/pagination state reload behavior is parity-safe.
- [ ] Reload hook exists when needed; naming follows `...V2` direction for new work.
- [ ] Globals are controlled and minimal (capabilities/context/container/reload only).

---

## 5. API/Endpoint Checks

- [ ] Endpoint strings do not include `/api/` prefix.
- [ ] No raw `fetch/axios` for JSON APIs (file upload exception only).
- [ ] Success/failure branches are handled explicitly.

---

## 6. Delivery Scope Checks

- [ ] Patch stayed within requested scope.
- [ ] No broad refactor introduced.
- [ ] Any legacy compatibility retained is explicit and justified.
