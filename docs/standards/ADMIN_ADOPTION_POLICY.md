# Admin Control Panel Standards Adoption Policy

**Scope:** Admin Control Panel only

This document records project-specific compatibility decisions for the pinned
standards snapshot. It is not an upstream standard and does not authorize any
change to `Maatify/php-engineering-standards`.

## Precedence

For Admin-specific conflicts, use this order after the owner's current
instruction and applicable `AGENTS.md` files:

1. `docs/PROJECT_CANONICAL_CONTEXT.md` and activated security or architecture decisions.
2. `docs/API.md` and the Admin API/template contract.
3. This policy for the explicit compatibility decisions below.
4. The generic standards snapshot under `docs/standards/`.
5. Supporting or historical documentation.

An unlisted conflict must be reported and must not be resolved by inference.

## Explicit Admin decisions

### Bootstrap and entrypoint

- The project starts at `docs/index.md`; that file includes the mandatory AI
  collaboration standard in its reading order.
- The web/API entrypoint is `public/admin/index.php`.
- References in the local Slim standard to `public/index.php` are adopted as
  `public/admin/index.php` for this project.

### API authority

- `docs/API.md` is the official API contract.
- Controller docblocks are supplementary implementation documentation and do
  not replace API documentation.

### Permissions

- Permissions are flat and explicit.
- Dropdown, autocomplete, and select datasets require a dedicated endpoint
  and dedicated permission such as `products.select`.
- A list or mutation permission does not implicitly grant selection access.

### ID validation

- The strict canonical positive ID contract from the Base Module profile applies
  to Base, Slim, and Project-Aware code in Admin.
- IDs accept only canonical positive `int|string` values.
- Reject floats, booleans, null, signs, whitespace, leading zeros, decimals,
  scientific notation, and overflow before casting.
- The permissive `is_numeric()` Project-Aware example is not adopted; the local
  Project-Aware copy contains the strict equivalent.

### Existing module documentation

- Existing Storage, ExchangeRates, and Settings documents are retained as
  `legacy/as-is` implementation references.
- They are not compliance declarations for the current standards snapshot.
- Future changes are governed by the current local standards and this policy;
  migrating legacy implementation is a separate task.
