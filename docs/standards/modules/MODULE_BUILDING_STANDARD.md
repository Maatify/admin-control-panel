# MODULE_BUILDING_STANDARD

**Maatify Base Module Profile — v1**
This document defines the law for building any new standalone Base Module in the Maatify ecosystem.

---

## 1. Profile Relationships & Precedence

To ensure clarity in modular architecture, the following profile relationships are strictly enforced:

- **Base Module:** Exclusively designed as a reusable/extractable Composer Package.
- **Slim Module Wrapper:** An optional, highly specialized wrapper around **a single Base Module** (e.g., `MODULE_SLIM_BUILDING_STANDARD`). It provides Admin HTTP/UI integration and must not duplicate core business logic.
- **Project-Aware Module:** Host-specific and non-extractable. It never overrides Base Module rules unless explicitly named as an exception inside the Project-Aware profile itself.
- Non-extractable modules DO NOT implicitly expand Base. They must use the Project-Aware profile or establish a separate documented profile decision.
- All applicable Base Module rules, including those for Persistence and PDO, remain in effect when the module possesses Database behavior.

---

## 2. Standards Cross-References

This standard strictly owns Base Module boundaries and profile relationships. Generic package mechanics are delegated to their canonical owners to prevent competing sources of truth. The module MUST adhere to:

- **Package Architecture & Testing:** [PACKAGE_BUILDING_STANDARD.md](../packages/PACKAGE_BUILDING_STANDARD.md) owns generic runtime/test mechanics, static analysis, DB architecture, and exception classification policies.
- **Composer Metadata:** [COMPOSER_PACKAGE_STANDARD.md](../packages/COMPOSER_PACKAGE_STANDARD.md) owns dependency rules.
- **CI Checks:** [CI_WORKFLOW_STANDARD.md](../packages/CI_WORKFLOW_STANDARD.md) owns GitHub Actions workflow execution.
- **Library Presentation:** [LIBRARY_PRESENTATION_STANDARD.md](../packages/LIBRARY_PRESENTATION_STANDARD.md) owns READMEs, badges, and documentation formatting. The module must use the canonical root Package Reference; do not create a competing Module Reference.

---

## 3. Directory Structure, Boundaries, and The Module Contract

- The `src/` directory should follow **Domain-driven boundaries** as its primary organizing principle.
- **Admin/Customer Namespaces:** Are **CONDITIONAL/OPTIONAL** and apply only if the specific module domain imposes such a strict boundary.
- **Business Orchestration:** Services strictly own business orchestration.
- **Validation:** Commands/inputs strictly own input validation (syntactic/domain), not business orchestration.
- **Framework-Neutrality:** The Base Module MUST NOT contain Controllers, Routes, Middleware, Permissions, Twig, JavaScript, Admin UI, or Export presentation details.
- **Replaceability:** Public replaceable capabilities must use framework-neutral contracts/interfaces where applicable.

---

## 4. Host Integration & Isolation Boundaries

- **No Host-Side JOINs:** The Base Module MUST NOT perform FK constraints, JOINs, or existence resolution on tables owned by the Host.
- **Module-Local JOINs:** The module is only permitted to perform **module-local/domain-local JOINs** within its own defined tables.
- **Input Validation vs Trust:** While the module does not perform database-level FK resolution for host IDs, it is fully responsible for the syntactic and domain validation of input contracts. This includes enforcing the canonical positive ID representation for host-provided IDs.

---

## 5. Dependency Injection

- The Base Module MUST NOT contain `Bootstrap/Bindings` or any framework-specific PHP-DI wiring. Container wiring is strictly the responsibility of the Host.
- **Factories/Providers:** Allowed ONLY when they are framework-neutral and serve the internal construction/integration needs owned exclusively by the package itself, not for host application wiring.

---

## 6. Conditional Database & Persistence Rules

All PDO, SQL, schema, migrations, transactions, and database testing rules are **CONDITIONAL** and apply ONLY when the module has inherent Persistence/Database behavior.

When applicable:
- **PDO-based:** All persistence uses PDO directly (no ORMs). Small internal SQL fragment builders are allowed.
- **Host-agnostic IDs:** Document missing foreign keys to host tables with `COMMENT 'Host-provided ID. No FK.'`.
- **PDO Named Placeholder Policy:** To support Native Prepared Statements and ensure cross-driver compatibility within the Maatify ecosystem, unique named placeholders MUST be used. Every placeholder MUST appear exactly once per SQL string, as a compatibility policy rather than claiming absolute driver restrictions in all modes.
- **Ordering and Pagination:** Do NOT re-implement shared Ordering/Pagination. Refer to `maatify/persistence` via [PACKAGE_BUILDING_STANDARD.md](../packages/PACKAGE_BUILDING_STANDARD.md). The module handles only its module-local entity-deletion orchestration, security/domain filters, module-local JOINs, selected columns, mapping, and the public contract.

---

## 7. Exception Rules

- **Hierarchy:** Exceptions MUST use the appropriate hierarchy from `maatify/exceptions`.
- **Marker Interface:** Keep a local marker interface extending `Throwable` (e.g., `{ModuleName}ExceptionInterface`).
- **Flexible Domain Exceptions:** Do NOT force a fixed list of Exceptions on every module. Create named domain exceptions only when genuinely distinct.
- **Constructors:** Named constructors SHOULD be used when there is a stable semantic reason. Direct instantiation MAY be used when the contract allows.
- **SQLSTATE Constraints:** Do NOT assume every `23xxx` error is a duplicate key. Rely on [PACKAGE_BUILDING_STANDARD.md](../packages/PACKAGE_BUILDING_STANDARD.md) for safe, driver-specific classification of errors.

---

## 8. Input Validation Rules

### 8.1 Date Validation
- For strict known-format date-only inputs, you MUST use `DateTimeImmutable::createFromFormat()` followed by an **exact round-trip string comparison** to guard against silent normalization or overflow. Inspecting `getLastErrors()` is an additional check, not a substitute for the canonical round-trip.
- Do NOT ban `new \DateTimeImmutable()` for general, free-form date-time parsing.

### 8.2 ID Validation
- For Primary Key / ID validation, neither `is_numeric()` nor `filter_var()` with INT validation is sufficient alone.
- You MUST enforce a canonical positive ID contract accepting `int|string` only.
- Reject float, bool, null, signs (`+`/`-`), whitespace, leading zeros, decimal, and scientific notation.
- You MUST check bounds and prevent overflow before casting to `int`.

---

## 9. PHPStan Rules

Do not duplicate package standard configurations. Note the following specifics:
- **Hydration & Casting:** At PHPStan Level max, casting a `mixed` value to `int` is rejected and requires type narrowing. However, `(bool) mixed` is not generally rejected.
- **Generics:** `IteratorAggregate` implementations MUST declare generics (`@implements \IteratorAggregate<TKey, TValue>`).

---

## 10. Decimal / Financial Rules

- All monetary and fixed-precision values MUST remain `string` (DECIMAL precision).
- It is strictly **forbidden** to cast monetary values to `float`.
- Validate decimal formats strictly before applying any `bcmath` operations.

---

## 11. Presentation vs Persistence Separation

**Persistence/Query layers NEVER transform data for display.**

- Keep the framework-neutral rule: The Repository/Query layer returns raw database values. Any formatting, encoding, or display-oriented transformation belongs exclusively in the Presentation layer.
- Repositories/Query layers own persistence only (when applicable), never display formatting.

---

## 12. Conditional Patterns (Translation & Analytics)

Translation patterns and Pre-Aggregated Analytics are **CONDITIONAL/OPTIONAL** based on the module's domain.
- The module must document its required consistency, currency, idempotency, and transaction boundaries according to its actual needs.
- Any detailed implementation blueprints belong in an independent Guide, not as general baseline rules inside this Profile.

---

## 13. Base Module Completion Checklist

- [ ] Profile relationships and precedence strictly maintained (Base Module is extractable).
- [ ] All cross-references correctly point to canonical `standards/packages/` documents.
- [ ] No framework-specific UI, Controller, Routing, Twig logic, or container wiring exists in the module.
- [ ] All package-defined exceptions follow the appropriate hierarchy correctly.
- [ ] Strict type and canonical ID validation is enforced, while maintaining no Host-table FK/JOIN inside the Base Module.
- [ ] Conditional persistence applicability documented, with no re-implementation of Ordering/Pagination.
- [ ] No financial/monetary values are cast to `float`.
