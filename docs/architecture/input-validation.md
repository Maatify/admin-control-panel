# 🧱 Input Validation Architecture
**Status:** ACTIVE  
**Owner:** Architecture / Application Layer  
**Scope:** Cross-Cutting Module  
**Last Updated:** 2026-01-09

---

## 🎯 1. Purpose & Motivation

The goal of the Input Validation module is to provide:

- Deterministic validation of inbound data from UI and API
- Consistent error semantics
- Guard-layer integration (Security & Authorization)
- Clear feedback to the UI about invalid input fields
- Runtime-safe validation (not debug-only)
- Framework-agnostic, library-assisted validation rules

This replaces unsafe patterns such as:

- `assert(...)`-based validation
- Ad-hoc manual checks scattered across controllers
- Exception-only error signaling
- “Fail-open” validation modes

---

## 📦 2. Module Responsibilities

The Input Validation module MUST:

✔ Validate inbound request payloads  
✔ Apply rules derived from DTOs / form models  
✔ Provide error structures suitable for UI consumption  
✔ Integrate with the Authentication Guard (Step-Up / Failure Semantics)  
✔ Remain side-effect-free (no DB, no messaging)  

And MUST NOT:

✘ Modify business state  
✘ Perform authorization checks  
✘ Emit domain events  
✘ Enforce security policies beyond input safety  

---

## 🎨 3. Design Principles

This module follows these architectural principles:

### (1) **Framework-Agnostic**
No dependence on Slim / Laravel form validators.

### (2) **Library-Augmented**
We will adopt a third-party validation library for rules.

**Chosen Library:**
> `respect/validation`

### (3) **Guard-Friendly**
Validation outputs map into Guard failure semantics.

Example:
- Validation error → `INPUT_INVALID`
- Authentication required later → `STEP_UP_REQUIRED`

### (4) **UI-Aware Failure Semantics**
Errors MUST be structured to highlight *which* fields failed.

---

## 🧩 4. Module Structure

Recommended namespace:

```

App/
└── Validation/
    └── Modules/
        ├── Validator/
        ├── Rules/
        ├── Schemas/
        ├── Exceptions/
        ├── Contracts/
        └── ErrorMapper/

```

### Roles:

| Component     | Responsibility                      |
|---------------|-------------------------------------|
| `Rules`       | Low-level reusable rule definitions |
| `Schemas`     | Per-feature validation schemas      |
| `Validator`   | Runtime execution & error capture   |
| `ErrorMapper` | Formats errors for UI/API responses |
| `Exceptions`  | Domain-safe exception signaling     |

---

## 🧪 5. Validation Execution Flow

Typical validation flow for inbound input:

```

Raw Payload
↓
Schema Selection (DTO-aware)
↓
Rule Execution (Respect/Validation)
↓
Collect Failures
↓
Error Mapping → UI/API structure
↓
Return to Controller or Guard

```

---

## 🧱 6. Respect/Validation Integration

We will use:

- Rule-based validation (scalars, arrays, email, etc.)
- Composite schemas for structured JSON
- Custom rules for domain-specific constraints

This reduces duplicated logic and keeps DTOs clean.

---

## 🔒 7. Guard Integration

Validation sits **before** authorization:

```

[Validation] → [Authentication] → [Authorization]

````

If validation fails:
- We DO NOT hit the guard
- We DO NOT create security noise

Guard failure semantics remain purely for:
- Auth
- Step-up
- Permission errors

---

## 🖥 8. UI / API Error Semantics

Validation errors MUST return a structure suitable for UI.

Example structure:

```json
{
  "error": "INPUT_INVALID",
  "fields": {
    "email": "Email format invalid",
    "password": "Must be at least 8 characters"
  }
}
````

Controller/Guard decides HTTP code.

Suggested mapping:

| Type             | HTTP Code | Error Code         |
|------------------|-----------|--------------------|
| Validation Error | `400`     | `INPUT_INVALID`    |
| Auth Required    | `401`     | `AUTH_REQUIRED`    |
| Step-Up Auth     | `403`     | `STEP_UP_REQUIRED` |
| Forbidden        | `403`     | `NOT_AUTHORIZED`   |

---

## 🧱 9. Error Modes

Validation failure SHOULD NOT:

* Throw `AssertionError`
* Throw `RuntimeException`

Instead:

* Collect errors
* Map to structured error
* Return via Controller → UI

For internal logic layers, exceptions may wrap validation as:

`ValidationFailedException`

But it MUST NOT leak raw assertion or stack traces.

---

## 🏗 10. DTO & Schema Alignment

Schemas SHOULD reflect DTO fields.

Example conventions:

* DTO defines *shape*
* Schema defines *rules*
* Controller binds schema → DTO

This keeps layers clean and testable.

---

## 📦 11. Module Reusability & Future Extraction

This module is designed to be extracted in the future into:

> `maatify/input-validation` library

Therefore:

* No reliance on project-specific models
* No circular dependencies
* No UI framework coupling
* Respect/Validation must be optional dependency

---

## 🚧 12. Current Status & Next Steps

**Current Status:**

* Decision made
* Library selected (`respect/validation`)
* Architecture defined
* Guard semantics aligned

**Next Steps:**

1. Introduce base module scaffolding
2. Add primitive rules (string, email, int, etc.)
3. Add DTO schema bindings
4. Add controller integration
5. Add UI error mapper

---

## 📚 13. References & Related Documents

* `docs/PROJECT_CANONICAL_CONTEXT.md` (Cross-Cutting Concerns)
* `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md` (UI Semantics)
* `docs/API.md` (Response Contract)
* `docs/security/authentication-architecture.md` (Guard Semantics)

---

## 🔐 14. Non-Goals (Important)

This module does NOT:

* Replace authorization
* Replace audit logging
* Parse JSON at HTTP boundary
* Perform DB validation
* Perform business validation
* Normalize data
* Apply domain invariants

Those concerns belong to other layers.

---

## 🧾 15. Final Architectural Decision

> **Input Validation is a Cross-Cutting Architectural Module**
>
> It MUST be centralized, library-assisted, UI-aware, guard-friendly, and runtime-safe.
>
> **Library Choice:** `respect/validation`
>
> **Integration Mode:** Controllers → Schemas → Guard Error Mapping
