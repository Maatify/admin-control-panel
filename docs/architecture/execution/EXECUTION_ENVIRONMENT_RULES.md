# Execution Environment Rules

## 1. Namespace and Autoloading Rules
- Namespaces MUST follow the PSR-4 mapping defined in `composer.json`.
- DO NOT assume a namespace based on directory names.
- `composer.json` is the single source of truth for namespace resolution.
- If namespace is unclear:
  → STOP and verify mapping before implementation.

## 2. Validation Integration Rules
- Validation MUST follow existing Validation module contracts.
- DO NOT assume base classes or rule formats.
- If unclear:
  → STOP and inspect the Validation module before implementation.
  → DO NOT implement validation until contract is confirmed.

## 3. Permission Mapping Rules
- All NEW routes with `->setName(...)` MUST be explicitly mapped in:
  `app/Modules/AdminKernel/Domain/Security/PermissionMapperV2.php`
- This requirement applies ONLY to NEW routes.
- DO NOT modify existing mappings unless explicitly required.
- Missing mapping will fail CI (permission-lint).

## 4. Static Analysis (PHPStan) Compatibility Rules
- Applies to NEW and MODIFIED code only.
- Code MUST use strict typing.
- Code MUST define explicit array shapes (e.g., `array<string, string>`).
- Code MUST annotate generics for framework interfaces.
- MUST NOT refactor legacy code solely for static analysis compliance.
- If type requirements are unclear:
  → STOP and resolve before implementation.
