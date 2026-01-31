# Kernel Boundaries & Extension Policy

**Project:** maatify/admin-control-panel  
**Status:** Canonical / Locked  
**Audience:** Core Maintainers & Host Application Integrators

---

## 1. Purpose of This Document

This document defines the **non-negotiable boundaries** of the Admin Kernel.

Its goals are to:

- Prevent accidental modification of security-critical logic
- Clearly separate **Core**, **Extensible**, and **Internal** components
- Provide a stable contract for host applications
- Ensure long-term maintainability and upgrade safety

If a component is marked **LOCKED**, it MUST NOT be overridden, replaced, or extended.

---

## 2. Boundary Classification

Every part of the system belongs to exactly **one** of the following categories:

| Category       | Meaning                                                               |
|----------------|-----------------------------------------------------------------------|
| **CORE**       | Kernel-owned, security or behavior critical. Must never change.       |
| **EXTENSIBLE** | Explicitly designed extension points via interfaces or configuration. |
| **INTERNAL**   | Implementation details. Not part of the public kernel contract.       |

---

## 3. CORE (LOCKED — DO NOT OVERRIDE)

These components define the **security, authorization, and behavioral guarantees**
of the Admin Kernel.

### 3.1 Authentication & Authorization

- `Maatify\AdminKernel\Domain\Service\AdminAuthenticationService`
- `Maatify\AdminKernel\Domain\Service\AuthorizationService`
- `Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware`
- `Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware`
- `Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware`
- `Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware`
- `Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware`

**Rationale:**  
Any modification here risks bypassing authentication, RBAC, or session guarantees.

---

### 3.2 Routing Semantics

- Route **names**
- Route → Permission mapping
- Step-Up enforcement logic
- Middleware execution order

**Note:**  
Routes may be *mounted* or *extended*, but their semantics are immutable.

---

### 3.3 Domain Contracts

- `Maatify\AdminKernel\Domain\Contracts\*`
- DTOs under `Maatify\AdminKernel\Domain\DTO\*`

**Rationale:**  
These are the kernel’s public API. Changing them breaks compatibility.

---

### 3.4 Kernel Documentation

- `docs/PROJECT_CANONICAL_CONTEXT.md`
- `docs/API.md`
- `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`
- `docs/KERNEL_BOUNDARIES.md`

These documents are **authoritative**.

---

## 4. EXTENSIBLE (HOOK-ONLY — SAFE OVERRIDES)

These components are intentionally designed for host-level customization.

### 4.1 UI Extension

#### Navigation
- Interface:  
  `Maatify\AdminKernel\Domain\Contracts\Ui\NavigationProviderInterface`
- Default Implementation:  
  `Maatify\AdminKernel\Infrastructure\Ui\DefaultNavigationProvider`

**Extension Mechanism:**  
Container binding override.

---

#### Assets
- Configuration DTO:  
  `Maatify\AdminKernel\Domain\DTO\Ui\UiConfigDTO`
- Environment Variable:  
  `ASSET_BASE_URL`

Used to relocate assets to CDN or sub-path.

---

### 4.2 Routing Extension

- Entry Point:  
  `AdminRoutes::register(...)`
- Extension Pattern:  
  Additional route providers mounted by the host

**Allowed:**
- Adding new UI pages
- Adding new API endpoints
- Mounting under a prefix (e.g. `/admin`)

**Forbidden:**
- Changing existing route paths
- Rebinding route names
- Removing kernel routes

---

### 4.3 Dependency Injection

Allowed via **Container Builder Hook**.

Examples of allowed overrides:

- Navigation Provider
- Repositories with identical contracts
- Infrastructure adapters (email transport, storage)

**Forbidden:**
- Replacing authentication services
- Replacing authorization services
- Replacing guard middleware

---

## 5. INTERNAL (NO DEPENDENCY GUARANTEE)

These components may change without notice.

Host applications MUST NOT rely on them.

Examples:

- Concrete repository implementations
- Internal helper classes
- UI controllers implementation details
- Twig template structure (except exposed globals)
- SQL query details

---

## 6. Override Rules (Summary)

| Action                       | Allowed |
|------------------------------|---------|
| Bind new services            | ✅       |
| Override Navigation Provider | ✅       |
| Add UI routes                | ✅       |
| Add API routes               | ✅       |
| Change auth logic            | ❌       |
| Change RBAC behavior         | ❌       |
| Modify middleware order      | ❌       |
| Replace Domain contracts     | ❌       |

---

## 7. Final Rule

> **If it is not explicitly marked as EXTENSIBLE, assume it is LOCKED.**

Any change that violates this document is considered a **kernel breach**.

---

**End of Document**
