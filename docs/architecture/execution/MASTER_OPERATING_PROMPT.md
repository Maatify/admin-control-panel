# MASTER OPERATING PROMPT

## SOURCE OF TRUTH (MANDATORY)

Use this order:
1. Task request (user/developer constraints)
2. Runtime reality in current branch
3. `docs/API/{feature}.md` contracts (behavioral WHAT)
4. Execution docs in `docs/architecture/execution/` (implementation HOW)

If implementation docs conflict with current runtime reality, align with reality and mark docs for correction.

---

## FRONTEND CANONICAL BASELINE (CURRENT)

For all new/edited frontend work in v2-active areas:

1. **Bridge-first orchestration is mandatory**
   - Use `AdminPageBridge` as the primary page facade.
   - Prefer `AdminPageBridge.API.execute/runMutation` for API flows.

2. **Family-local helper seam is mandatory**
   - Use family helper layers (e.g., `I18nHelpersV2`) for table target swaps, scoped tableAction state, reset/reload handlers, modal dismiss helpers.

3. **Bridge.Table is the page-level table orchestration boundary**
   - Use helper/bridge table targeting and scoped tableAction handling.
   - Do not hardcode broad global `tableAction` handling when scoped helper path exists.

4. **Twig ↔ JS contract is two-tiered**
   - Tier A (default/simple): use `#table-container`.
   - Tier B (non-default): Twig must inject explicit runtime globals (context + container id), and JS must consume them.

5. **Mount order for v2 pages is bridge-first**
   - infra scripts → `admin-page-bridge.js` → family helper v2 → page v2 module(s).

6. **Escaping policy for v2 pages**
   - Use `AdminPageBridge.Text.escapeHtml(...)`.

7. **Reload naming direction for new work**
   - Use `...V2` naming direction for new reload globals.
   - Legacy aliases may be kept only for compatibility.

8. **Smallest safe v2 file shape**
   - Do not force fixed split (`helpers/core/modals/actions`) when a single v2 module is safer and sufficient.
   - Choose shape by parity/risk and existing family baseline.

9. **Superseded legacy-file handling**
   - Do not auto-migrate a legacy file if an active v2 family already fully owns that runtime.
   - Treat as superseded unless there is proven unique behavior.

---

## API CONTRACT RULE (MANDATORY)

- `docs/API/{feature}.md` defines behavior contract (WHAT).
- Execution docs define implementation path (HOW).
- If contract and reality conflict, flag explicitly and do not silently choose one.

---

## EXECUTION PIPELINE (FRONTEND)

1. Confirm feature runtime path is v2-active vs legacy/hybrid.
2. Read feature API contract if present.
3. Identify page runtime contracts from Twig (capabilities/context/container globals).
4. Select smallest safe v2 shape for task scope.
5. Implement with Bridge + family helper seam.
6. Validate parity-critical behaviors (table state, scoped events, mutation reload timing, modal lifecycle).
7. Run syntax/file checks and report scope-limited verification.

---

## HARD FRONTEND CONSTRAINTS

- Do not introduce new parallel architecture when bridge/helper baseline exists.
- Do not preserve outdated legacy execution paths as equal defaults in new docs/work.
- Do not broaden migrations beyond requested scope.
