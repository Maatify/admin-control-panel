Below is a **STRICT AI-EXECUTOR CHECKLIST**.
It is written to be followed **literally** by an AI or human executor.
If **any checkbox cannot be confidently checked → STOP execution**.

---

# 🤖 AI-EXECUTOR STRICT CHECKLIST

## Config-Driven CRUD Builder System

**Mode:** STRICT
**Tolerance:** ZERO ambiguity
**Rule:** *Do not assume. Do not improvise. Do not optimize.*

---

## 🔒 GLOBAL EXECUTION RULES (MANDATORY)

Before starting, verify **ALL**:

* [ ] I will not modify any legacy feature
* [ ] I will not migrate existing code
* [ ] I will not copy JavaScript from old features
* [ ] I will not add feature-specific JS unless explicitly required
* [ ] I will not mix legacy JS with the new system
* [ ] I will stop immediately if instructions conflict

If **any item above is false → ABORT**

---

## 🧱 PHASE 1 — GLOBAL NAMESPACE

### Preconditions

* [ ] No existing global named `AdminCRUD`
* [ ] No dependency on other modules

### Execution

* [ ] Create `window.AdminCRUD`
* [ ] Attach only empty containers:

    * `Utils`
    * `Modules`
    * `Renderers`
    * `Callbacks`
* [ ] No logic
* [ ] No DOM access
* [ ] No API calls

### Validation

* [ ] File loads with zero runtime errors
* [ ] Other files can safely attach to the namespace

❌ If logic exists → FAIL
❌ If DOM/API used → FAIL

---

## 🧠 PHASE 2 — SMART UTILS

### Preconditions

* [ ] Namespace exists and is clean

### Execution

Implement **only** the following functions:

* [ ] `detectFieldType(fieldName)`
* [ ] `detectRenderer(columnName)`
* [ ] `generateLabel(fieldName)`
* [ ] `generateSlug(text)`

### Rules

* [ ] Pure functions only
* [ ] No DOM
* [ ] No rendering
* [ ] No API calls
* [ ] No feature awareness

### Validation

* [ ] `is_active` → toggle / status
* [ ] `created_at` → date
* [ ] `slug` → code
* [ ] No config required for detection

❌ If UI logic exists → FAIL

---

## 🧩 PHASE 3 — CONFIG NORMALIZER (CORE)

### Preconditions

* [ ] Utils fully implemented
* [ ] No UI builders referenced

### Execution

Normalizer must:

* [ ] Accept minimal config
* [ ] Output complete executable config
* [ ] Auto-generate:

    * columns
    * filters
    * pagination
    * actions
    * modals
    * labels & titles

### Rules

* [ ] Pure transformation
* [ ] No DOM
* [ ] No rendering
* [ ] No side effects

### Validation

* [ ] Feature works with ≥70% missing config
* [ ] No UI logic exists outside builders

❌ If DOM touched → FAIL
❌ If API called → FAIL

---

## 🎨 PHASE 4 — UI BUILDERS

### GLOBAL RULE (CRITICAL)

Each UI module:

* [ ] Knows **nothing** about:

    * feature name
    * API endpoint
    * business rules
* [ ] Reads **only normalized config**
* [ ] Does **one responsibility only**

---

### 4.1 FILTER BUILDER

* [ ] Renders filters
* [ ] Binds events
* [ ] Produces query params only

❌ If API called → FAIL

---

### 4.2 TABLE BUILDER

* [ ] Renders headers
* [ ] Renders rows
* [ ] Applies renderers
* [ ] Handles pagination

❌ If feature name referenced → FAIL

---

### 4.3 MODALS & FORMS

* [ ] Auto-generated from config
* [ ] Create / Edit / Delete supported
* [ ] Validation UI only (no business logic)

❌ If modal content hardcoded → FAIL

---

## ⚙️ PHASE 5 — ACTION HANDLER

### Execution

* [ ] Standard actions implemented:

    * edit
    * delete
    * toggle
* [ ] Custom actions via config only
* [ ] Lifecycle callbacks supported

### Rules

* [ ] No feature-specific handlers
* [ ] No hardcoded logic
* [ ] Callbacks injected, not defined here

### Validation

* [ ] All actions work without writing JS in feature

❌ If feature JS required → FAIL

---

## 🎼 PHASE 6 — ORCHESTRATOR (BUILDER)

### Execution

Builder must:

* [ ] Load config
* [ ] Normalize config
* [ ] Initialize filters
* [ ] Initialize table
* [ ] Initialize modals
* [ ] Bind actions

### Rules

* [ ] This is the **only** file that knows all modules
* [ ] No other module references siblings

### Validation

```js
new AdminCRUD.Builder(config).init();
```

* [ ] Boots full CRUD feature

❌ If cross-module imports exist → FAIL

---

## 🧩 PHASE 7 — TWIG INTEGRATION

### Execution

* [ ] Single shared `crud-builder.twig`
* [ ] Injects config
* [ ] Injects containers
* [ ] Loads scripts only

### Feature Template

* [ ] ≤10 lines
* [ ] No JS
* [ ] No logic

### Validation

* [ ] Feature requires only:

    * 1 Twig file
    * 1 JSON config file

❌ If JS written in feature → FAIL

---

## 🧪 PHASE 8 — REAL FEATURE TEST (SCOPES)

### Execution

* [ ] Implement Scopes feature
* [ ] Use config + twig only

### Mandatory Success Metrics

* [ ] Implementation time ≤ 1 hour
* [ ] Total code < 200 lines
* [ ] Zero feature-specific JS
* [ ] CRUD fully functional
* [ ] Filters work
* [ ] Pagination works
* [ ] Permissions respected

❌ If any metric fails → SYSTEM INVALID

---

## 🚨 STOP CONDITIONS (IMMEDIATE ABORT)

Stop execution if:

* [ ] A decision is required but not specified
* [ ] Legacy code is needed to proceed
* [ ] Feature-specific JS seems “easier”
* [ ] Assumptions are required
* [ ] Instructions conflict

---

## 🧠 FINAL AI EXECUTION MANTRA

> **“Follow the checklist, not intuition.”**
> **“Config defines behavior.”**
> **“If unsure, stop.”**

---
