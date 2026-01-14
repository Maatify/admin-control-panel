# Password Crypto Module Viability Report

## 1. Executive Summary
**Recommendation: B) NOT ADAPTABLE**

The existing module `App\Modules\Crypto\Password` is designed around a "Single Pepper" architecture, where the pepper is implicit and static. This design is **fundamentally incompatible** with the application's mandatory "Pepper Ring" architecture, which requires:
1.  **Multiple Peppers:** Support for rotation and history.
2.  **Deterministic Verification:** The ability to verify a password using a *specific* pepper ID (not trial-and-error).
3.  **Upgrade-on-Login:** Logic to detect when a hash uses an old pepper ID.

Adapting the module would require breaking its core interfaces (`PasswordHasherInterface`), changing its return types, and replacing its dependency injection model. This constitutes a rewrite, not an adaptation. The module should be considered for replacement or deletion rather than reuse.

## 2. Explicit Gap Analysis

| Feature | Application Requirement (Canonical) | Module Capability (`PasswordHasher`) | Gap Type |
| :--- | :--- | :--- | :--- |
| **Verification Input** | `verify(plain, hash, pepper_id)` | `verify(plain, hash)` | **Architectural** (Interface) |
| **Verification Logic** | Use specific pepper ID (Deterministic) | Use current pepper (Implicit) | **Architectural** |
| **Hashing Output** | `array{hash, pepper_id}` | `string` (hash only) | **Interface** |
| **Dependency** | `PasswordPepperRing` (Map of secrets) | `PasswordPepperProviderInterface` (Single secret) | **Configuration** |
| **Rehash Strategy** | Check Argon options OR Pepper ID | Check Argon options ONLY | **Logic** |

## 3. Adaptation Effort

| Capability | Effort | Justification |
| :--- | :--- | :--- |
| **Pepper Ring Injection** | **MODERATE** | Requires replacing `PasswordPepperProviderInterface` with a new `PepperRingInterface`. |
| **Deterministic Verify** | **MAJOR** | **Breaking Change.** `PasswordHasherInterface::verify` MUST accept `$pepperId`. Without this, the hasher cannot know which pepper to use without violating the "No Trial-and-Error" rule. |
| **Return Metadata** | **MAJOR** | **Breaking Change.** `hash()` must return the `pepper_id` so it can be stored. Returning just the string makes the hash orphan (unknown pepper). |
| **Argon Policy** | **TRIVIAL** | `ArgonPolicyDTO` is compatible and can be reused. |

## 4. Design Risks

### Risk 1: Implicit vs Explicit State
The module assumes the "current" pepper is always the correct one for verification. This works for simple apps but fails for enterprise/long-lived apps where key rotation is required. Forcing this module to support rotation without changing the interface would require "Trial-and-Error" verification (looping through all peppers), which opens timing attack vectors and performance issues, violating the "Deterministic Verification" constraint.

### Risk 2: Interface Segregation
Adapting the module would result in a "Hybrid" interface that might confuse consumers (e.g., optional `pepper_id`). It is cleaner to define a new contract (`PepperedPasswordHasherInterface`) than to mangle the existing simple one.

## 5. Library vs Application Boundary

| Component | Layer | Status in Module |
| :--- | :--- | :--- |
| **Hashing Algo (Argon2id)** | Library | ✅ Covered |
| **Peppering (HMAC)** | Library | ✅ Covered (but assumes single key) |
| **Pepper Ring Management** | Library (Generic) | ❌ Missing (App-side only currently) |
| **Pepper Configuration** | Application | N/A (DI injected) |
| **Storage (Hash + ID)** | Application | ❌ Output format is insufficient |

The module currently attempts to cover "Hashing + Peppering" but fails to separate the *mechanism* of peppering from the *policy* of which pepper to use.

## 6. Final Recommendation

**B) NOT ADAPTABLE**

Do not attempt to evolve `App\Modules\Crypto\Password\PasswordHasher`.
-   Its interface is too simple for the requirements.
-   Refactoring it to support Pepper Ring would break any theoretical existing consumers and result in a class that looks nothing like the original.
-   **Exception:** `ArgonPolicyDTO` is well-designed and **SHOULD** be salvaged/moved to the Domain or a new library location if duplication reduction is desired.

## 7. Closure Status Statement

This analysis **does NOT** affect the current password closure status. The application uses `App\Domain\Service\PasswordService`, which is fully compliant. This report purely concerns the viability of an unused module for future library consolidation.
