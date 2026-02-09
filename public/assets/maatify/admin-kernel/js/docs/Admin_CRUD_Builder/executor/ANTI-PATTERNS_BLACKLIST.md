Below is the **🔐 ANTI-PATTERNS BLACKLIST**.
This is a **hard enforcement document**.
If **any item appears in code → the execution is INVALID**.

---

# 🔐 ANTI-PATTERNS BLACKLIST

## Config-Driven CRUD Builder System

**Audience:** Human developers & AI executors
**Mode:** STRICT
**Rule:** *Detection of any blacklisted pattern = STOP + REVERT*

---

## ☠️ CATEGORY 1 — ARCHITECTURAL VIOLATIONS

### ❌ 1. Feature-Aware Modules

**Description:**
Any module references a specific feature name (`scopes`, `languages`, `users`, etc.)

**Examples (FORBIDDEN):**

```js
if (feature === 'scopes') { ... }
```

**Why it’s fatal:**
Breaks reusability and makes the system non-generic.

**Correct Rule:**
Modules only consume **normalized config**, never feature identity.

---

### ❌ 2. Cross-Module Knowledge

**Description:**
One UI module directly imports or references another UI module.

**Examples (FORBIDDEN):**

```js
TableBuilder.call(FilterRenderer);
```

**Why it’s fatal:**
Destroys isolation and creates hidden coupling.

**Correct Rule:**
Only the **Builder (Orchestrator)** knows all modules.

---

### ❌ 3. Business Logic in UI

**Description:**
Any business rule appears in renderers, modals, tables, or filters.

**Examples (FORBIDDEN):**

```js
if (row.status === 'pending' && user.isAdmin) { ... }
```

**Why it’s fatal:**
UI becomes untestable and non-portable.

**Correct Rule:**
Business logic lives **outside** the CRUD system or inside callbacks.

---

## ☠️ CATEGORY 2 — CONFIG SYSTEM CORRUPTION

### ❌ 4. Mandatory Config Fields

**Description:**
The system requires verbose or full configs to work.

**Examples (FORBIDDEN):**

```json
"title": "...",
"breadcrumb": "...",
"actions": [...]
```

(required every time)

**Why it’s fatal:**
Defeats the purpose of smart defaults.

**Correct Rule:**
Config must work even if **70% is missing**.

---

### ❌ 5. Hardcoded Defaults

**Description:**
Defaults are hardcoded inside UI builders instead of the normalizer.

**Examples (FORBIDDEN):**

```js
columns = columns || ['id', 'name'];
```

**Why it’s fatal:**
Creates inconsistent behavior across modules.

**Correct Rule:**
**All defaults live in Config Normalizer only.**

---

### ❌ 6. Feature Logic in Config

**Description:**
Config contains executable logic instead of declarative data.

**Examples (FORBIDDEN):**

```json
"visibleIf": "user.role === 'admin'"
```

**Why it’s fatal:**
Turns config into code → security & maintainability risk.

**Correct Rule:**
Config is declarative. Logic lives in callbacks.

---

## ☠️ CATEGORY 3 — LEGACY CONTAMINATION

### ❌ 7. Copy-Pasting from Legacy Features

**Description:**
Any JS copied from Languages / old features.

**Examples (FORBIDDEN):**

```js
// copied from languages-actions.js
```

**Why it’s fatal:**
Reintroduces duplication and tight coupling.

**Correct Rule:**
Legacy code is **REFERENCE ONLY**, never reused.

---

### ❌ 8. Mixing Old JS with New System

**Description:**
New feature imports or depends on legacy JS files.

**Examples (FORBIDDEN):**

```twig
<script src="languages-modals.js"></script>
```

**Why it’s fatal:**
Creates unpredictable runtime behavior.

---

## ☠️ CATEGORY 4 — FEATURE-LEVEL VIOLATIONS

### ❌ 9. Feature-Specific JavaScript

**Description:**
Any JS written specifically for a feature.

**Examples (FORBIDDEN):**

```js
// scopes.js
```

**Why it’s fatal:**
Breaks the “config-only feature” guarantee.

**Correct Rule:**
Features use:

* JSON config
* Twig include
* Optional callbacks (shared)

---

### ❌ 10. JS Inside Feature Twig

**Description:**
Inline `<script>` inside feature templates.

**Examples (FORBIDDEN):**

```twig
<script>
  customLogic();
</script>
```

**Why it’s fatal:**
Unreviewable, non-scalable, unsafe.

---

## ☠️ CATEGORY 5 — EXECUTION FLOW BREAKERS

### ❌ 11. Multiple Entry Points

**Description:**
More than one place initializes the system.

**Examples (FORBIDDEN):**

```js
new FilterRenderer();
new TableBuilder();
```

**Why it’s fatal:**
Destroys lifecycle guarantees.

**Correct Rule:**
Only:

```js
new AdminCRUD.Builder(config).init();
```

---

### ❌ 12. Side Effects Outside Builder

**Description:**
API calls, mutations, or state changes outside the orchestrator.

**Examples (FORBIDDEN):**

```js
fetch('/api/...') // inside table builder
```

---

## ☠️ CATEGORY 6 — AI EXECUTION FAILURES

### ❌ 13. Assumption-Driven Coding

**Description:**
Executor “fills gaps” instead of stopping.

**Examples (FORBIDDEN):**

> “Probably they want…”

**Correct Rule:**
If unclear → **STOP**

---

### ❌ 14. Silent Deviation

**Description:**
Executor deviates from roadmap “for improvement”.

**Why it’s fatal:**
This is not a creative task.

---

### ❌ 15. Partial Compliance

**Description:**
Most checklist items pass, some ignored.

**Rule:**
**98% compliance = FAILURE**

---

## 🚨 IMMEDIATE TERMINATION TRIGGERS

Execution must stop immediately if:

* Any blacklist item is detected
* A decision is required but not documented
* Legacy code is needed to continue
* Feature-specific JS is “tempting”
* Executor starts optimizing instead of following

---

## 🧠 FINAL ENFORCEMENT PRINCIPLE

> **“If it feels convenient, it’s probably forbidden.”**
> **“Config over code. Builder over intuition.”**
> **“Stop is success. Guessing is failure.”**

---
