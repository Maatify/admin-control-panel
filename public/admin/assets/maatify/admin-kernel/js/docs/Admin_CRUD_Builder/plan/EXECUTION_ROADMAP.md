Perfect.
Below is a **strict, execution-safe roadmap in English**, written so that **a human developer or an AI executor can follow it without ambiguity, interpretation, or architectural drift**.

This is **handover-ready**.

---

# 📍 EXECUTION ROADMAP

## Config-Driven CRUD Builder System

**Project:** Admin CRUD Builder
**Audience:** Developer / AI Executor
**Objective:** Build a reusable, config-driven CRUD system
**Guarantee:** If followed *exactly*, no architectural mistakes will occur

---

## 🎯 FINAL GOAL (NON-NEGOTIABLE)

* One reusable CRUD system
* Any new CRUD feature is implemented in **30–60 minutes**
* A feature consists of:

    * One JSON config file
    * One minimal Twig file
* No feature-specific JavaScript unless explicitly required (escape hatches)

---

## 🚫 ABSOLUTE RULES (READ FIRST)

These rules are **hard constraints**:

### ❌ FORBIDDEN

* Modifying any existing legacy feature
* Migrating old features to the new system
* Reusing or copying JavaScript from legacy features
* Writing feature-specific UI logic
* Mixing legacy JS with the new system

### ✅ ALLOWED

* New features only
* Config-driven behavior only
* Escape hatches **only when strictly necessary**

> **Legacy features (e.g. Languages) are REFERENCE ONLY.**

---

# 🧱 PHASE 1 — Global Foundation

**Duration:** 0.5 day
**Purpose:** Prepare a stable base with zero logic

### Tasks

1. Create a single global namespace:

```js
window.AdminCRUD
```

2. Internal structure:

```js
AdminCRUD = {
  Utils: {},
  Modules: {},
  Renderers: {},
  Callbacks: {}
}
```

### Files

* `admin-crud-namespace.js`

### Rules

* No DOM access
* No API calls
* No feature awareness

### Exit Criteria

* Any subsequent file can safely attach to the namespace
* No runtime errors

---

# 🧠 PHASE 2 — Smart Utilities & Detection

**Duration:** 0.5 day
**Purpose:** Enable automatic behavior inference

### Tasks

Implement the following utilities:

* `detectFieldType(fieldName)`
* `detectRenderer(columnName)`
* `generateLabel(fieldName)`
* `generateSlug(text)`

### Files

* `admin-crud-utils.js`

### Rules

* Pure logic only
* No DOM
* No rendering
* No API calls

### Exit Criteria

* Field names automatically resolve to correct UI types and renderers
* No configuration required for common cases

---

# 🧩 PHASE 3 — Config Normalizer (CORE MODULE)

**Duration:** 1 day
**Purpose:** Convert minimal config into a fully executable definition

### Responsibilities

The normalizer must automatically generate:

* Table column definitions
* Default renderers
* Filters
* Pagination
* Standard actions
* Default modals (create/edit/delete)
* Titles, labels, breadcrumbs

### Files

* `admin-crud-config-normalizer.js`

### Rules

* **Pure transformation only**
* No DOM
* No rendering
* No side effects

### Exit Criteria

* A feature works even if the config is 70% incomplete
* No UI logic exists outside the builder system

---

# 🎨 PHASE 4 — UI Builders (STRICT MODULARITY)

**Duration:** 2 days
**Purpose:** Build UI strictly from normalized config

---

## 4.1 Filters Builder

**File:** `admin-crud-filter-renderer.js`

### Responsibilities

* Render filter UI
* Bind filter events
* Build query parameters

---

## 4.2 Table Builder

**File:** `admin-crud-table-builder.js`

### Responsibilities

* Render table headers
* Render rows
* Apply renderers
* Handle pagination

---

## 4.3 Modals & Forms

**Files:**

* `admin-crud-modal-generator.js`
* `admin-crud-form-builder.js`

### Responsibilities

* Create/Edit/Delete modals
* Form generation
* Validation UI

### Global Rules (CRITICAL)

* No module knows:

    * Feature name
    * API endpoint
    * Business logic
* All behavior is driven by config only

### Exit Criteria

* Full CRUD UI renders from config alone
* No feature-specific UI code exists

---

# ⚙️ PHASE 5 — Actions & Callbacks

**Duration:** 0.5 day
**Purpose:** Centralize all user interactions

### Responsibilities

* Standard actions:

    * Edit
    * Delete
    * Toggle
* Custom actions (via config)
* Lifecycle callbacks:

    * `beforeCreate`
    * `afterUpdate`
    * `validateForm`
    * `onError`

### Files

* `admin-crud-action-handler.js`

### Exit Criteria

* All actions work without feature-specific JS
* Custom behavior is injectable via callbacks only

---

# 🎼 PHASE 6 — Orchestrator (Builder)

**Duration:** 0.5 day
**Purpose:** Single execution entry point

### Responsibilities

Execution flow:

1. Load config
2. Normalize config
3. Initialize filters
4. Initialize table
5. Initialize modals
6. Bind actions

### Files

* `admin-crud-builder.js`

### Rules

* This is the **only** file aware of all modules
* Other modules must not reference each other

### Exit Criteria

```js
new AdminCRUD.Builder(config).init();
```

successfully boots a full feature

---

# 🧩 PHASE 7 — Twig Integration Layer

**Duration:** 0.5 day
**Purpose:** Minimal, safe server-side integration

### Files

* `crud-builder.twig`

### Usage Pattern

```twig
{% include 'crud-builder.twig' with { configFile: 'scopes.json' } %}
```

### Feature Files

* `scopes_list.twig` (≈10 lines)
* `scopes-config.json`

### Exit Criteria

* Any new feature requires **only two files**
* No JavaScript written inside feature templates

---

# 🧪 PHASE 8 — Real Feature Validation (Scopes)

**Duration:** 1 day
**Purpose:** Prove the system works in reality

### Tasks

* Implement Scopes feature
* Test:

    * CRUD operations
    * Filters
    * Pagination
    * Permissions
    * Actions

### Success Criteria (MANDATORY)

* ⏱ Implementation ≤ **1 hour**
* 📄 Code size < **200 lines**
* ❌ Zero feature-specific JavaScript

---

# 📊 TIMELINE SUMMARY

| Phase     | Duration            |
| --------- | ------------------- |
| Phase 1   | 0.5 day             |
| Phase 2   | 0.5 day             |
| Phase 3   | 1 day               |
| Phase 4   | 2 days              |
| Phase 5   | 0.5 day             |
| Phase 6   | 0.5 day             |
| Phase 7   | 0.5 day             |
| Phase 8   | 1 day               |
| **TOTAL** | **~7 working days** |

---

# 🧠 EXECUTION MENTAL MODEL

```
Config
  ↓
Normalizer
  ↓
UI Builders
  ↓
Actions
  ↓
Builder
  ↓
Feature Ready
```

---

# 📦 FINAL DELIVERABLES

* ✅ Fully working CRUD Builder system
* ✅ Config-driven feature creation
* ✅ Scopes feature as proof
* ✅ Zero coupling with legacy code
* ✅ Safe for humans AND AI executors

---

If you want next:

* 📄 **PDF handover version**
* 📋 **AI-executor strict checklist**
* 🧠 **Anti-patterns & forbidden implementations**
* 🗂 **Kanban / task board**

Tell me what you want to generate next.
