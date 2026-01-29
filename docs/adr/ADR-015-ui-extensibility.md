# ADR-015: UI Extensibility via Template Namespaces, Host Overrides, and Theme Slots

**Status:** Proposed
**Date:** 2026-01-29
**Decision ID:** ADR-015
**Scope:** Admin Control Panel – UI Layer
**Phase:** UI Extensibility – Phase 2 (Design Only)

---

## 1. Context (السياق)

The Admin Control Panel is designed as a **kernel-grade system** intended to be embedded or mounted inside host applications.
Phase 1 established a secure and centralized UI foundation using:

* Kernel-owned Twig templates
* Global `UiConfigDTO` injected as `ui`
* Fixed layout structure (`base.twig`)
* No supported mechanism for host-level UI customization

As adoption increases, host applications require **controlled UI customization** (branding, layout tweaks, asset overrides) **without modifying kernel files** and without compromising security or upgradeability.

---

## 2. Problem Statement

The current UI architecture does **not** support:

* Host-level template overrides
* Safe inheritance of kernel templates
* Granular customization of layout sections (head, sidebar, footer, scripts)

Any customization today would require:

* Forking templates
* Copying `base.twig`
* Risking divergence from kernel security updates

This violates the kernel’s **lockability and upgrade guarantees**.

---

## 3. Decision

We adopt a **file-based UI extensibility model** based on three pillars:

1. **Host Overrides (Filesystem Stacking)**
2. **Template Namespaces (`@admin`, `@host`)**
3. **Theme Slots (Granular Twig Blocks)**

This decision is **design-only** and introduces no runtime behavior changes in Phase 2.

---

## 4. Design Details

### 4.1 Host Overrides — Filesystem Stacking

**Decision:**
Twig will resolve templates using an ordered path stack:

1. Host templates (if configured)
2. Kernel templates (default)

**Effect:**
A host can override any kernel template by placing a file with the same name in its own directory.

**Example:**

```text
Host:   templates/admin/login.twig
Kernel: app/templates/login.twig
```

Twig loads the host version first.

---

### 4.2 Template Namespaces

**Decision:**
Introduce explicit namespaces:

* `@admin` → Kernel templates
* `@host` → Host templates

**Why:**
To allow **safe inheritance**, not just replacement.

**Example:**

```twig
{# Host login.twig #}
{% extends "@admin/login.twig" %}
```

This avoids infinite recursion and allows kernel logic (security tokens, flows) to remain intact.

---

### 4.3 Theme Slots (Layout Injection Points)

**Decision:**
Refactor `base.twig` to expose **granular blocks** (“slots”) instead of a monolithic layout.

**Defined Slots:**

* `head_meta`
* `head_assets`
* `sidebar_header`
* `sidebar_footer`
* `page_header`
* `content_footer`
* `body_scripts`

**Effect:**
Hosts can override *specific UI areas* without copying the full layout.

---

## 5. Consequences

### ✅ Positive

* Kernel remains upgrade-safe
* Host customization becomes explicit and controlled
* Security-sensitive templates can be extended instead of replaced
* Clear ownership: Kernel vs Host

### ⚠️ Trade-offs

* Slight increase in Twig loader configuration complexity
* Requires discipline in documenting available slots

---

## 6. Alternatives Considered

### ❌ Full Theme Engine

Rejected due to:

* Runtime complexity
* Security risks
* Scope creep

### ❌ Database-stored Templates

Rejected due to:

* Performance concerns
* Security model violation
* Debugging complexity

---

## 7. Non-Goals (Explicit)

* No plugin marketplace
* No runtime theme switching
* No CSS framework abstraction
* No database-driven UI

---

## 8. Implementation Notes (Future Phase)

* Twig loader to be updated with namespaced paths
* `base.twig` to be refactored into slot-based layout
* Optional host template path injected via container hook
* No breaking changes to Phase 1 APIs

---

## 8.1 Design Artifacts (Normative Reference)

This ADR is accompanied by an official, non-executable design document that
elaborates on the UI extensibility model in practical and conceptual terms.

The following document is considered **normative** for understanding and
implementing this decision:

- `docs/ui/UI_EXTENSIBILITY_PHASE2.md`

This document:
- Expands on the three extensibility pillars defined in this ADR
- Provides illustrative examples (non-runtime) for host developers
- Does NOT introduce additional requirements beyond this ADR

In case of ambiguity, this ADR remains the source of truth.
The design document serves as an explanatory and educational companion only.

---

## 9. Status & Next Steps

* ✅ Design approved conceptually
* ⏳ Implementation deferred to Phase 3
* 📌 This ADR **must be referenced** before any UI extensibility code is written

---

## 10. Final Verdict

This ADR formally locks the **UI Extensibility model** for the Admin Control Panel.
Any future UI customization **must** comply with this design to preserve kernel integrity.

---
