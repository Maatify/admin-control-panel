# Nested Transactions Architectural Audit & Readiness Review

## 1. Transaction Map

The following components initiate and manage database transactions (`beginTransaction`, `commit`, `rollBack`).

### Controllers (HTTP Layer)
| Component | Method | Scope | Notes |
| :--- | :--- | :--- | :--- |
| `App\Http\Controllers\AdminController` | `create` | **Initiator** | Manages full creation flow (Admin, Email, Password, Logs). |
| `App\Http\Controllers\Web\ChangePasswordController` | `change` | **Initiator** | Manages password update flow. |

### Application Services
| Component | Method | Scope | Notes |
| :--- | :--- | :--- | :--- |
| `App\Application\Admin\AdminProfileUpdateService` | `update` | **Initiator** | Updates admin profile. Uses `SessionRevocationService` (non-transactional method). |

### Domain Services
| Component | Method | Scope | Notes |
| :--- | :--- | :--- | :--- |
| `App\Domain\Service\AdminAuthenticationService` | `login` | **Initiator** | **Blind Start**. Manages session creation & password rehash. |
| `App\Domain\Service\AdminAuthenticationService` | `logoutSession` | **Initiator** | **Blind Start**. Manages revocation. |
| `App\Domain\Service\RememberMeService` | `issue` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\RememberMeService` | `processAutoLogin` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\RememberMeService` | `revoke*` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\RoleAssignmentService` | `assignRole` | **Initiator** | **Blind Start**. Calls `StepUpService::hasGrant` (before TX). |
| `App\Domain\Service\RoleAssignmentService` | `logDenial` | **Hybrid** | Checks `inTransaction()` (Safe). |
| `App\Domain\Service\SessionRevocationService` | `revoke*` | **Initiator** | **Blind Start**. Excludes `revokeAllActiveForAdmin`. |
| `App\Domain\Service\StepUpService` | `verifyTotp` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\StepUpService` | `enableTotp` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\StepUpService` | `logDenial` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\StepUpService` | `hasGrant` | **Conditional** | **Blind Start**. Triggered on risk mismatch or single-use consumption. |
| `App\Domain\Service\RecoveryStateService` | `*` | **Hybrid** | Checks `inTransaction()` (Safe). |
| `App\Domain\Service\AdminEmailVerificationService` | `verify`, `replace`, `fail` | **Initiator** | **Blind Start**. |
| `App\Domain\Service\TwoFactorEnrollmentService` | `enroll` | **Initiator** | **Blind Start**. |

### Infrastructure (Workers)
| Component | Method | Scope | Notes |
| :--- | :--- | :--- | :--- |
| `App\Modules\Email\Worker\EmailQueueWorker` | `process` | **Initiator** | Manages email processing batch. |

---

## 2. Risk Matrix

The following scenarios present concrete conflict risks due to "Blind Start" transactions (calling `beginTransaction()` without checking if one is active).

| Risk Level | Scenario | Conflict Details |
| :--- | :--- | :--- |
| **CRITICAL** | **Nested Step-Up Verification** | If `StepUpService::hasGrant` is called inside an active transaction (e.g., inside `AdminController::create` for future role assignment), it will crash on `beginTransaction()`. |
| **CRITICAL** | **Composite Session Revocation** | If `SessionRevocationService::revoke` (or bulk) is called inside an active transaction (e.g., "Suspend Admin" service), it will crash. |
| **HIGH** | **Auto-Login Composition** | `AdminAuthenticationService::login` cannot be composed inside `AdminController::create` (e.g., "Create & Login") because both start transactions. |
| **HIGH** | **Role Assignment Composition** | `RoleAssignmentService::assignRole` cannot be composed inside another transaction. |
| **MEDIUM** | **Remember-Me Composition** | `RememberMeService` operations cannot be composed. |

---

## 3. Compatibility Assessment

### Logging Modules
| Module | Transactional Status | Assessment |
| :--- | :--- | :--- |
| **Activity Log** | **Compatible** | Uses shared `PDO`. Fail-open (swallows errors). Will participate in parent transaction (atomic with business logic). |
| **Authoritative Audit** | **Compatible** | **Requires** active transaction. Guard clause `!inTransaction()` ensures integrity. |
| **Security Events** | **Compatible** | Best-effort. Uses shared `PDO`. |
| **Telemetry** | **Compatible** | Best-effort. Uses shared `PDO`. |

### Infrastructure
| Module | Transactional Status | Assessment |
| :--- | :--- | :--- |
| **Repositories** | **Safe** | Do not manage transactions. Use shared `PDO`. |
| **Email Worker** | **Safe** | Runs in isolated process. |

---

## 4. Architectural Recommendation (Conceptual)

To enable safe Nested Transactions, the following architecture is recommended:

### A. Transaction Manager Abstraction
Introduce a `TransactionManagerInterface` that abstracts PDO transaction logic.
- **Must support Nesting:** Use `SAVEPOINT` logic (if supported) or reference counting to handle nested `begin/commit` calls safely.
- **Method Signature:** `runInTransaction(callable $operation): mixed` is preferred over manual begin/commit to ensure generic rollback handling.

### B. Forbidden Locations
- **Domain Services** MUST NOT call `$pdo->beginTransaction()` directly. They must use the Transaction Manager.
- **Controllers** SHOULD NOT manage transactions directly; they should delegate to Application Services or use the Transaction Manager.

### C. Refactoring Strategy (Phased)
1.  **Introduce `TransactionManager`** (Infrastructure layer).
2.  **Replace `RecoveryStateService` & `RoleAssignmentService`** manual nesting logic with `TransactionManager`.
3.  **Update "Blind Start" Services** (`StepUpService`, `AdminAuthService`, etc.) to use `TransactionManager`.

---

## 5. Blockers / Unknowns

- **No immediate blockers** for *introducing* the abstraction.
- **Current Blocker for Composition:** The current codebase is **NOT READY** for service composition (nested calls) due to pervasive "Blind Start" transaction usage.
